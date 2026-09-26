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
 * Home summary external service.
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");
require_once("{$CFG->libdir}/enrollib.php");
require_once("{$CFG->libdir}/completionlib.php");

use context_system;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Returns the authenticated user's compact learning journey summary.
 */
class home_summary extends external_api {

    /**
     * Service parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Build the authenticated user's home summary.
     *
     * @return array
     * @throws \coding_exception
     * @throws \core_external\restricted_context_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     */
    public static function execute() {
        global $DB, $USER;

        self::validate_parameters(self::execute_parameters(), []);
        require_login();
        self::validate_context(context_system::instance());

        $courses = enrol_get_my_courses(null, "sortorder ASC");
        $courseids = [];
        foreach ($courses as $course) {
            $courseids[] = (int) $course->id;
        }

        if (!$courseids) {
            return [
                "progress" => 0,
                "completedactivities" => 0,
                "totalactivities" => 0,
                "activecourses" => 0,
                "completedcourses" => 0,
                "coursecount" => 0,
                "lastcourseid" => 0,
                "lastcourseaccess" => 0,
            ];
        }

        [$coursesql, $courseparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, "courseid");
        $activityparams = $courseparams + [
                "userid" => $USER->id,
                "completionnone" => COMPLETION_TRACKING_NONE,
                "complete" => COMPLETION_COMPLETE,
                "completepass" => COMPLETION_COMPLETE_PASS,
                "completefail" => COMPLETION_COMPLETE_FAIL,
            ];

        $activitysql = "SELECT COUNT(cm.id) AS totalactivities,
                               SUM(CASE
                                       WHEN cmc.completionstate IN (:complete, :completepass, :completefail) THEN 1
                                       ELSE 0
                                   END) AS completedactivities
                          FROM {course_modules} cm
                     LEFT JOIN {course_modules_completion} cmc
                            ON cmc.coursemoduleid = cm.id
                           AND cmc.userid = :userid
                         WHERE cm.course {$coursesql}
                           AND cm.completion <> :completionnone
                           AND cm.visible = 1
                           AND cm.deletioninprogress = 0";
        $activities = $DB->get_record_sql($activitysql, $activityparams);

        [$completioncoursesql, $completioncourseparams] =
            $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, "completioncourseid");
        $completioncourseparams["completionuserid"] = $USER->id;
        $completedcourses = $DB->count_records_select(
            "course_completions",
            "userid = :completionuserid
                 AND course {$completioncoursesql}
                 AND timecompleted IS NOT NULL",
            $completioncourseparams
        );

        [$lastcoursesql, $lastcourseparams] =
            $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, "lastcourseid");
        $lastcourseparams["lastuserid"] = $USER->id;
        $lastaccess = $DB->get_record_sql(
            "SELECT courseid, timeaccess
               FROM {user_lastaccess}
              WHERE userid = :lastuserid
                AND courseid {$lastcoursesql}
           ORDER BY timeaccess DESC",
            $lastcourseparams,
            IGNORE_MULTIPLE
        );

        $totalactivities = (int) ($activities->totalactivities ?? 0);
        $completedactivities = (int) ($activities->completedactivities ?? 0);
        $progress = $totalactivities
            ? (int) round(($completedactivities / $totalactivities) * 100)
            : 0;
        $coursecount = count($courseids);

        return [
            "progress" => $progress,
            "completedactivities" => $completedactivities,
            "totalactivities" => $totalactivities,
            "activecourses" => max($coursecount - $completedcourses, 0),
            "completedcourses" => $completedcourses,
            "coursecount" => $coursecount,
            "lastcourseid" => $lastaccess ? (int) $lastaccess->courseid : reset($courseids),
            "lastcourseaccess" => $lastaccess ? (int) $lastaccess->timeaccess : 0,
        ];
    }

    /**
     * Service return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            "progress" => new external_value(PARAM_INT, "Overall tracked activity progress."),
            "completedactivities" => new external_value(PARAM_INT, "Completed tracked activities."),
            "totalactivities" => new external_value(PARAM_INT, "Total tracked activities."),
            "activecourses" => new external_value(PARAM_INT, "Enrolled courses not marked complete."),
            "completedcourses" => new external_value(PARAM_INT, "Completed enrolled courses."),
            "coursecount" => new external_value(PARAM_INT, "Total enrolled courses."),
            "lastcourseid" => new external_value(PARAM_INT, "Most recently accessed enrolled course."),
            "lastcourseaccess" => new external_value(PARAM_INT, "Last course access timestamp."),
        ]);
    }
}
