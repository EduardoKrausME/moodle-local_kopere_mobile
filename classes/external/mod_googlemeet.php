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
 * Class mod_googlemeet
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");

/**
 * Class mod_googlemeet
 * @package local_kopere_mobile\external
 */
class mod_googlemeet extends \external_api {

    /**
     * mobile_parameters function
     *
     * @return \external_function_parameters
     */
    public static function mobile_parameters() {
        return new \external_function_parameters([
            "cmid" => new \external_value(PARAM_INT, 'mod instance id'),
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
     */
    public static function mobile($cmid) {
        global $CFG, $OUTPUT, $DB;

        if (file_exists("{$CFG->dirroot}/mod/googlemeet/classes/output/mobile.php")) {
            require_once("{$CFG->dirroot}/mod/googlemeet/classes/output/mobile.php");
            require_once("{$CFG->dirroot}/mod/googlemeet/lib.php");
            require_once("{$CFG->dirroot}/mod/googlemeet/locallib.php");

            $cm = get_coursemodule_from_id("googlemeet", $cmid);
            $googlemeet = $DB->get_record("googlemeet", ["id" => $cm->instance], '*', MUST_EXIST);

            $recordings = googlemeet_list_recordings(["googlemeetid" => $googlemeet->id, "visible" => true]);
            $hasrecordings = !empty($recordings);

            $data = [
                "intro" => $googlemeet->intro,
                "url" => $googlemeet->url,
                "cmid" => $cm->id,
                "upcomingevent" => googlemeet_get_upcoming_events($googlemeet->id),
                "recording" => [
                    "hasrecordings" => $hasrecordings,
                    "recordings" => $recordings,
                ],
            ];
            $html = $OUTPUT->render_from_template("mod_googlemeet/mobile_view_page_latest", $data);
            $html = str_replace("ion-list", "ons-list", $html);
            $html = str_replace("ion-item", "ons-list-item", $html);
            $html = str_replace("ion-label", "label", $html);
            $html = str_replace("ion-button", "ons-button", $html);
            $html = str_replace("ion-list-header", "ons-list-header", $html);
            $html = str_replace("ion-icon slot=\"", "ons-icon icon=\"ion-", $html);
            $html = str_replace("ion-icon", "ons-icon", $html);

            $html = preg_replace('/<core-course-module-description.*>/', "", $html);

            preg_match_all('/\{\{ \'plugin.(\w+).(\w+)\' \| translate }}/', $html, $translates);
            foreach ($translates[0] as $id => $translate) {

                $component = $translates[1][$id];
                $identifier = $translates[2][$id];

                $str = get_string($identifier, $component);
                $html = str_replace($translate, $str, $html);
            }

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
            "html" => new \external_value(PARAM_RAW, "HTML"),
        ]);
    }
}
