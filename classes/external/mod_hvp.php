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
 * Class mod_hvp
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
 * Class mod_hvp
 * @package local_kopere_mobile\external
 */
class mod_hvp extends \external_api {

    /**
     * mobile_parameters function
     *
     * @return \external_function_parameters
     */
    public static function mobile_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'mod instance id'),
        ]);
    }

    /**
     * mobile function
     *
     * @param int $cmid
     *
     * @return array of warnings and status result
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \Exception
     */
    public static function mobile($cmid) {
        global $DB, $CFG, $OUTPUT, $USER;

        if (file_exists("{$CFG->dirroot}/mod/hvp/classes/output/mobile.php")) {
            require_once("{$CFG->dirroot}/mod/hvp/classes/mobile_auth.php");

            // Verify course context.
            $cm = get_coursemodule_from_id('hvp', $cmid);
            if (!$cm) {
                return ["html" => 'invalidcoursemodule'];
            }
            $course = $DB->get_record('course', ['id' => $cm->course]);
            if (!$course) {
                return ["html" => 'coursemisconf'];
            }

            list($token, $secret) = \mod_hvp\mobile_auth::create_embed_auth_token();

            // Store secret in database.
            $auth = $DB->get_record('hvp_auth', [
                'user_id' => $USER->id,
            ]);
            $currenttimestamp = time();
            if ($auth) {
                $DB->update_record('hvp_auth', [
                    'id' => $auth->id,
                    'secret' => $token,
                    'created_at' => $currenttimestamp,
                ]);
            } else {
                $DB->insert_record('hvp_auth', [
                    'user_id' => $USER->id,
                    'secret' => $token,
                    'created_at' => $currenttimestamp,
                ]);
            }

            $data = [
                'cmid' => $cmid,
                'wwwroot' => $CFG->wwwroot,
                'user_id' => $USER->id,
                'secret' => urlencode($secret),
            ];
            $html = $OUTPUT->render_from_template('mod_hvp/mobile_view_page', $data);

            return ["html" => $html];
        }
        return ["html" => "Plugin not found"];
    }

    /**
     * mobile_returns function
     *
     * @return \external_single_structure
     */
    public static function mobile_returns() {
        return new \external_single_structure([
            'html' => new \external_value(PARAM_RAW, 'HTML'),
        ]);
    }
}
