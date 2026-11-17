<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU GPL for more details.

namespace local_kopere_mobile;

defined('MOODLE_INTERNAL') || die();

/**
 * Issue and consume single-use, 60-second redirect JWTs.
 *
 * The target URL is kept in Moodle's database, never in a JWT claim or in a
 * token-bearing query string sent to the destination. No permanent WS token
 * is accepted here. All JWT verification and redemption happens server-side.
 */
final class external_link {
    public const TTL = 60;
    private const TABLE = 'local_kopere_mobile_links';
    private const AUDIENCE = 'local_kopere_mobile/redirect';

    /**
     * Validate the destination, including URLs originating in course content.
     */
    public static function validate_target(string $target): string {
        global $CFG;

        if (strlen($target) > 4096 || strlen($target) < 10 ||
                preg_match('/[\x00-\x20\x7f\\\\]/', $target) ||
                !filter_var($target, FILTER_VALIDATE_URL)) {
            throw new \invalid_parameter_exception('Invalid external link destination');
        }

        $parsed = parse_url($target);
        if (!is_array($parsed) || strtolower($parsed['scheme'] ?? '') !== 'https' ||
                empty($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
            throw new \invalid_parameter_exception('Only HTTPS destinations without credentials are supported');
        }
        $host = strtolower(trim($parsed['host'], '[]'));
        $root = parse_url($CFG->wwwroot);
        $internal = is_array($root) &&
            strtolower($parsed['host']) === strtolower($root['host'] ?? '') &&
            (int) ($parsed['port'] ?? 443) === (int) ($root['port'] ?? 443) &&
            strtolower($parsed['scheme']) === strtolower($root['scheme'] ?? '');
        if (!$internal) {
            if ($host === 'localhost' || preg_match('/\.(?:localhost|local|internal)$/', $host) ||
                    (filter_var($host, FILTER_VALIDATE_IP) &&
                    !filter_var($host, FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
                throw new \invalid_parameter_exception('Local or private destinations are not permitted');
            }
        }

        foreach (explode('&', $parsed['query'] ?? '') as $pair) {
            $name = strtolower(rawurldecode(explode('=', $pair, 2)[0]));
            if (in_array($name, ['token', 'wstoken', 'privatetoken', 'sesskey', 'jwt'], true)) {
                throw new \invalid_parameter_exception('Credential parameters cannot be redirected');
            }
        }
        return $target;
    }

    /** True only for URLs under the configured Moodle installation. */
    public static function is_internal(string $target): bool {
        global $CFG;
        $root = parse_url($CFG->wwwroot);
        $url = parse_url($target);
        if (!is_array($root) || !is_array($url) ||
                strtolower($root['scheme'] ?? '') !== strtolower($url['scheme'] ?? '') ||
                strtolower($root['host'] ?? '') !== strtolower($url['host'] ?? '') ||
                (int) ($root['port'] ?? 443) !== (int) ($url['port'] ?? 443)) {
            return false;
        }
        $base = rtrim($root['path'] ?? '', '/');
        $path = $url['path'] ?? '/';
        return $base === '' || $path === $base || strpos($path, $base . '/') === 0;
    }

    private static function encode(string $text): string {
        return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
    }

    private static function decode(string $text): ?string {
        if ($text === '' || !preg_match('/^[A-Za-z0-9_-]+$/D', $text)) {
            return null;
        }
        $decoded = base64_decode(strtr($text, '-_', '+/') . str_repeat('=', (4 - strlen($text) % 4) % 4), true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * A per-Moodle random signing key is initialized under the site's shared lock.
     */
    private static function secret(bool $create): ?string {
        $secret = get_config('local_kopere_mobile', 'external_link_secret');
        if (is_string($secret) && preg_match('/^[a-f0-9]{64}$/D', $secret)) {
            return hex2bin($secret);
        }
        if (!$create) {
            return null;
        }

        $lock = \core\lock\lock_config::get_lock_factory('local_kopere_mobile')
            ->get_lock('external-link-secret', 10);
        if (!$lock) {
            throw new \moodle_exception('error');
        }
        try {
            $secret = get_config('local_kopere_mobile', 'external_link_secret');
            if (!is_string($secret) || !preg_match('/^[a-f0-9]{64}$/D', $secret)) {
                $secret = bin2hex(random_bytes(32));
                set_config('external_link_secret', $secret, 'local_kopere_mobile');
            }
            return hex2bin($secret);
        } finally {
            $lock->release();
        }
    }

    /** Create a signed ticket; caller must already be authenticated by Moodle WS. */
    public static function issue(string $target, int $userid): array {
        global $CFG, $DB;

        if ($userid <= 0) {
            throw new \invalid_parameter_exception('Invalid authenticated user');
        }
        $target = self::validate_target($target);
        $now = time();
        $jti = bin2hex(random_bytes(24));
        $claims = [
            'iss' => $CFG->wwwroot,
            'aud' => self::AUDIENCE,
            'sub' => (string) $userid,
            'jti' => $jti,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + self::TTL,
        ];
        $header = self::encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::encode(json_encode($claims));
        $signature = self::encode(hash_hmac('sha256', $header . '.' . $payload, self::secret(true), true));
        $DB->insert_record(self::TABLE, (object) [
            'jti' => hash('sha256', $jti),
            'userid' => $userid,
            'target' => $target,
            'expires' => $now + self::TTL,
            'timecreated' => $now,
        ]);
        return ['jwt' => $header . '.' . $payload . '.' . $signature, 'expiresat' => $now + self::TTL];
    }

    /**
     * Verify then atomically redeem a ticket. A shared Moodle lock prevents
     * concurrent requests from successfully using the same ticket twice.
     *
     * @return array|null ['target' => HTTPS URL, 'userid' => Moodle user ID]
     */
    public static function consume(string $jwt): ?array {
        global $CFG, $DB;

        if (strlen($jwt) > 2048 ||
                !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D', $jwt)) {
            return null;
        }
        [$encodedheader, $encodedclaims, $signature] = explode('.', $jwt);
        $header = json_decode(self::decode($encodedheader) ?? '', true);
        $claims = json_decode(self::decode($encodedclaims) ?? '', true);
        $secret = self::secret(false);
        if (!$secret || $header !== ['alg' => 'HS256', 'typ' => 'JWT'] ||
                !is_array($claims) ||
                !hash_equals(self::encode(hash_hmac('sha256',
                    $encodedheader . '.' . $encodedclaims, $secret, true)), $signature)) {
            return null;
        }

        $now = time();
        if (($claims['iss'] ?? null) !== $CFG->wwwroot ||
                ($claims['aud'] ?? null) !== self::AUDIENCE ||
                !is_string($claims['sub'] ?? null) ||
                !preg_match('/^[1-9][0-9]*$/D', $claims['sub']) ||
                !is_string($claims['jti'] ?? null) ||
                !preg_match('/^[a-f0-9]{48}$/D', $claims['jti']) ||
                !is_int($claims['iat'] ?? null) ||
                !is_int($claims['nbf'] ?? null) ||
                !is_int($claims['exp'] ?? null) ||
                $claims['nbf'] !== $claims['iat'] ||
                $claims['exp'] !== $claims['iat'] + self::TTL ||
                $claims['iat'] > $now ||
                $now >= $claims['exp']) {
            return null;
        }

        $hash = hash('sha256', $claims['jti']);
        $lock = \core\lock\lock_config::get_lock_factory('local_kopere_mobile')
            ->get_lock('external-link-' . $hash, 5);
        if (!$lock) {
            return null;
        }
        try {
            $record = $DB->get_record(self::TABLE, ['jti' => $hash]);
            if (!$record || (int) $record->expires <= time() ||
                    (int) $record->userid !== (int) $claims['sub']) {
                return null;
            }
            // Consume before redirect, including when the browser aborts.
            $DB->delete_records(self::TABLE, ['id' => $record->id]);
            return ['target' => $record->target, 'userid' => (int) $record->userid];
        } finally {
            $lock->release();
        }
    }

    /** Remove unredeemed expired records, without ever revealing their URLs. */
    public static function purge_expired(): void {
        global $DB;
        $DB->delete_records_select(self::TABLE, 'expires <= :now', ['now' => time()]);
    }
}
