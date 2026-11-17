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

namespace local_kopere_mobile;

use local_kopere_mobile\external\create_external_link;

/** @covers \local_kopere_mobile\external_link */
final class external_link_test extends \advanced_testcase {

    public function test_issue_and_single_use(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $issued = create_external_link::execute('https://docs.moodle.org/en/Main_page?foo=bar');
        \external_api::clean_returnvalue(create_external_link::execute_returns(), $issued);
        $this->assertSame(time() + 60, $issued['expiresat'], '', 2);
        $this->assertSame(3, count(explode('.', $issued['jwt'])));
        $this->assertStringNotContainsString('docs.moodle.org', $issued['jwt']);

        $resolved = external_link::consume($issued['jwt']);
        $this->assertSame('https://docs.moodle.org/en/Main_page?foo=bar', $resolved['target']);
        $this->assertSame((int) $user->id, $resolved['userid']);
        $this->assertNull(external_link::consume($issued['jwt']));
    }

    public function test_invalid_signature_and_claims_are_rejected(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $jwt = external_link::issue('https://example.org/path', (int) $user->id)['jwt'];
        $parts = explode('.', $jwt);
        $changed = substr($parts[2], 0, -2) . 'zz';
        $this->assertNull(external_link::consume($parts[0] . '.' . $parts[1] . '.' . $changed));
        $this->assertNull(external_link::consume('not-a-jwt'));
        $this->assertNotNull(external_link::consume($jwt));
    }

    public function test_expired_database_ticket_cannot_be_redeemed(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $jwt = external_link::issue('https://example.org/test', (int) $user->id)['jwt'];
        $DB->set_field('local_kopere_mobile_links', 'expires', time() - 1);
        $this->assertNull(external_link::consume($jwt));
        external_link::purge_expired();
        $this->assertEquals(0, $DB->count_records('local_kopere_mobile_links'));
    }

    public function test_destination_validation_prevents_credentials_and_unsafe_urls(): void {
        $this->resetAfterTest();
        foreach ([
            'javascript:alert(1)',
            'http://example.org',
            'https://user:pass@example.org',
            'https://127.0.0.1/private',
            'https://localhost/private',
            'https://example.org/?wstoken=abc',
            'https://example.org/?%74oken=secret',
            "https://example.org/\r\nLocation: https://evil.test",
        ] as $target) {
            try {
                external_link::validate_target($target);
                $this->fail('Unsafe target accepted');
            } catch (\invalid_parameter_exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $this->assertSame('https://example.org/a?b=c',
            external_link::validate_target('https://example.org/a?b=c'));
    }

    public function test_different_users_have_distinct_authenticated_tickets(): void {
        $this->resetAfterTest();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        $this->setUser($first);
        $a = create_external_link::execute('https://example.org/a');
        $this->setUser($second);
        $b = create_external_link::execute('https://example.org/a');
        $this->assertNotSame($a['jwt'], $b['jwt']);
        $this->assertSame((int) $first->id, external_link::consume($a['jwt'])['userid']);
        $this->assertSame((int) $second->id, external_link::consume($b['jwt'])['userid']);
    }

    public function test_guest_cannot_mint_a_ticket(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->expectException(\required_capability_exception::class);
        create_external_link::execute('https://example.org/');
    }

    public function test_moodle_origin_is_checked_as_an_origin_not_string_prefix(): void {
        global $CFG;
        $this->resetAfterTest();
        $root = parse_url($CFG->wwwroot);
        $scheme = $root['scheme'];
        $host = $root['host'];
        $this->assertTrue(external_link::is_internal($CFG->wwwroot . '/course/view.php?id=1'));
        $this->assertFalse(external_link::is_internal($scheme . '://' . $host . '.evil.example/course/view.php'));
    }
}
