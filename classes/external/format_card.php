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
 * Class format_card
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

use tool_dataprivacy\external\data_request_exporter;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");

/**
 * Class format_card
 * @package local_kopere_mobile\external
 */
class format_card extends \external_api {

    /**
     * get_structure_parameters function
     *
     * @return \external_function_parameters
     */
    public static function get_structure_parameters() {
        return new \external_function_parameters([
            "courseid" => new \external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * get_structure function
     * @param int $courseid
     *
     * @return array
     * @throws \Exception
     */
    public static function get_structure($courseid) {
        global $PAGE, $CFG;

        require_once("{$CFG->dirroot}/course/format/cards/lib.php");

        $PAGE->set_context(\context_course::instance($courseid));

        if (file_exists("{$CFG->dirroot}/course/format/cards/classes/output/courseformat/content/section/header.php")) {

            require_once(__DIR__ . "/format_card_moodleorg.php");
            $cards = new format_card_moodleorg();
            $itens = $cards->get_cards($courseid);

            header_remove("Access-Control-Allow-Origin");
            header("Content-Type: application/json");
            header("Access-Control-Allow-Origin: *");

            die(json_encode([
                "version" => "moodleorg",
                "structures" => $itens,
            ], JSON_PRETTY_PRINT));

        } else {
            require_once(__DIR__ . "/format_card_smartlms.php");

            $cards = new format_card_smartlms();
            $itens = $cards->get_cards($courseid);

            header_remove("Access-Control-Allow-Origin");
            header("Content-Type: application/json");
            header("Access-Control-Allow-Origin: *");

            die(json_encode([
                "version" => "smartlms",
                "structures" => $itens,
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * get_structure_returns function
     *
     * @return \external_description
     */
    public static function get_structure_returns() {
        return new \external_single_structure([
            "cards" => data_request_exporter::get_read_structure(),
        ]);
    }
}
