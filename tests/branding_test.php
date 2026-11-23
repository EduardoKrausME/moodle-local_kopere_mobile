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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for public mobile branding.
 *
 * @package local_kopere_mobile
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile;

/**
 * Verify graceful fallback when LearnLingo is not in use.
 *
 * @covers \\local_kopere_mobile\\branding
 */
final class branding_test extends \advanced_testcase {
    public function test_fallback_when_theme_is_not_learnlingo(): void {
        $this->resetAfterTest();
        set_config('theme', 'boost');

        $result = branding::export();

        $this->assertSame('moodle', $result['source']);
        $this->assertSame('#08422A', $result['brandcolor']);
        $this->assertSame('', $result['navbar_scss']);
    }
}
