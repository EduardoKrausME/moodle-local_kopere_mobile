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
 * Class mod_make_view
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");

/**
 * Class mod_make_view
 * @package local_kopere_mobile\external
 */
class mod_make_view extends \external_api {

    /**
     * make_view_parameters function
     *
     * @return \external_function_parameters
     */
    public static function make_view_parameters() {
        return new \external_function_parameters([
            'modid' => new \external_value(PARAM_INT, 'mod instance id'),
            'modname' => new \external_value(PARAM_TEXT, 'mod instance name'),
        ]);
    }

    /**
     * make_view function
     *
     * @param int $modid
     * @param string $modname
     *
     * @return array of warnings and status result
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public static function make_view($modid, $modname) {
        global $DB, $CFG;

        if (file_exists("{$CFG->dirroot}/mod/{$modname}/lib.php")) {
            require_once("{$CFG->dirroot}/mod/{$modname}/lib.php");

            // Request and permission validation.
            $mod = $DB->get_record($modname, ['id' => $modid], '*', MUST_EXIST);
            list($course, $cm) = get_course_and_cm_from_instance($mod, $modname);

            $context = \context_module::instance($cm->id);
            self::validate_context($context);

            require_capability("mod/{$modname}:view", $context);

            // Call the mod/lib API.
            $functionname = "{$modname}_view";
            if (function_exists($functionname)) {
                $functionname($mod, $course, $cm, $context);
                return ["status" => true];
            }
        }
        return ["status" => false];
    }

    /**
     * make_view_returns function
     *
     * @return \external_single_structure
     */
    public static function make_view_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_BOOL, 'Status'),
        ]);
    }
}
