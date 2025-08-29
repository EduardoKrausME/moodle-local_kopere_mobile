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
 * Class mod_subcourse
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");

use context_course;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

/**
 * Class mod_subcourse
 *
 * @package local_kopere_mobile\external
 */
class mod_subcourse extends external_api {

    /**
     * mobile_parameters function
     *
     * @return external_function_parameters
     */
    public static function mobile_parameters() {
        return new external_function_parameters([
            "instanceid" => new external_value(PARAM_INT, 'subcourse id'),
        ]);
    }

    /**
     * mobile function
     *
     * @param $instanceid
     * @return array
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function mobile($instanceid) {
        global $DB, $USER;

        $subcourse = $DB->get_record("subcourse", ["id" => $instanceid]);

        if ($subcourse) {
            $refcourse = $DB->get_record("course", ["id" => $subcourse->refcourse]);
            if ($refcourse) {
                $contextcourseref = context_course::instance($refcourse->id);
                if (!has_capability('moodle/course:view', $contextcourseref)) {
                    \local_kopere_dashboard\util\enroll_util::enrol($refcourse, $USER, 0, 5);
                }

                return [
                    "refcourse" => $refcourse->id,
                ];
            }
        }

        return [
            "refcourse" => 0,
        ];
    }

    /**
     * mobile_returns function
     *
     * @return external_description
     */
    public static function mobile_returns() {
        return new external_single_structure([
            "refcourse" => new external_value(PARAM_INT, ""),
        ]);
    }
}
