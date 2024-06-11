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
 * Class format_card_moodleorg
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

/**
 * Class format_card_moodleorg
 * @package local_kopere_mobile\external
 */
class format_card_moodleorg {
    /**
     * get_cards function
     *
     * @param $courseid
     *
     * @return array
     * @throws \Exception
     */
    public function get_cards($courseid) {
        global $PAGE, $CFG;

        require_once("{$CFG->dirroot}/course/format/lib.php");
        require_once("{$CFG->dirroot}/course/format/cards/classes/output/courseformat/content/section/header.php");
        require_once("{$CFG->dirroot}/course/format/cards/classes/output/renderer.php");

        /** @var \core_courseformat\base $format */
        $format = course_get_format($courseid);
        $modinfo = $format->get_modinfo();

        $itens = [];
        foreach (self::get_sections_to_display($format, $modinfo) as $sectionnum => $section) {
            $header = new \format_cards\output\courseformat\content\section\header($format, $section);

            $renderer = new \format_cards\output\renderer($PAGE, null);

            $itens[] = $header->export_for_template($renderer);
        }

        return $itens;
    }

    /**
     * get_sections_to_display function
     *
     * @param \core_courseformat\base $format
     * @param \course_modinfo $modinfo
     *
     * @return array
     * @throws \Exception
     */
    private static function get_sections_to_display($format, $modinfo): array {
        $singlesection = $format->get_section_number();
        if ($singlesection) {
            return [
                $modinfo->get_section_info(0),
                $modinfo->get_section_info($singlesection),
            ];
        }

        return $modinfo->get_section_info_all();
    }
}
