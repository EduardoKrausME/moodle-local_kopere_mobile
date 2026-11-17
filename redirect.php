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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__ . '/../../config.php');

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src ' . "'none'" . '; frame-ancestors ' . "'none'");
header('Cross-Origin-Resource-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$jwt = optional_param('jwt', '', PARAM_RAW);
try {
    $link = \local_kopere_mobile\external_link::consume($jwt);
    if (!$link) {
        throw new \moodle_exception('error');
    }
    $target = \local_kopere_mobile\external_link::validate_target($link['target']);
    $user = $DB->get_record('user', ['id' => $link['userid']], '*');
    if (!$user || !empty($user->deleted) || !empty($user->suspended) || empty($user->confirmed)) {
        throw new \moodle_exception('error');
    }

    // Only URLs inside this Moodle installation receive a browser session.
    // An external website never sees the token or a new Moodle session cookie.
    if (\local_kopere_mobile\external_link::is_internal($target)) {
        \core\session\manager::login_user($user);
    }
    header('Location: ' . $target, true, 303);
    exit;
} catch (\Throwable $e) {
    http_response_code(410);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'This link is invalid, expired or already used.';
    exit;
}
