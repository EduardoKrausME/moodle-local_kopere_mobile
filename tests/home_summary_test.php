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
 * Tests for the authenticated mobile home summary web service.
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile;

use local_kopere_mobile\external\home_summary;

/**
 * Regression tests for the mobile home summary.
 *
 * The user identity is provided by the test environment, without performing
 * a browser login, as with a token-authenticated external service.
 *
 * @covers \local_kopere_mobile\external\home_summary
 * @runTestsInSeparateProcesses
 */
final class home_summary_test extends \advanced_testcase {

    /**
     * Users without enrolled courses should receive a valid, zero-valued summary.
     */
    public function test_execute_without_enrolments(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $result = home_summary::execute();

        $this->assertSame([
            'progress' => 0,
            'completedactivities' => 0,
            'totalactivities' => 0,
            'activecourses' => 0,
            'completedcourses' => 0,
            'coursecount' => 0,
            'lastcourseid' => 0,
            'lastcourseaccess' => 0,
        ], $result);

        // Ensure the actual response matches the declared external API contract.
        \external_api::clean_returnvalue(home_summary::execute_returns(), $result);
    }

    /**
     * The service should return the current user's enrolled course summary.
     */
    public function test_execute_for_enrolled_user(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $result = home_summary::execute();

        $this->assertSame(1, $result['coursecount']);
        $this->assertSame(1, $result['activecourses']);
        $this->assertSame(0, $result['completedcourses']);
        $this->assertSame((int)$course->id, $result['lastcourseid']);
        \external_api::clean_returnvalue(home_summary::execute_returns(), $result);
    }
}
