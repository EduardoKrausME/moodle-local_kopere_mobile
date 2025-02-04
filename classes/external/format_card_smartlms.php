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
 * Class format_card_smartlms
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile\external;

use format_cards\pluginfile\cards;

/**
 * Class format_card_smartlms
 * @package local_kopere_mobile\external
 */
class format_card_smartlms {
    /**
     * @var int
     */
    protected $level;

    /**
     * get_cards function
     *
     * @param $courseid
     *
     * @return array
     * @throws \Exception
     */
    public function get_cards($courseid) {
        global $CFG;

        require_once("{$CFG->dirroot}/course/format/cards/classes/pluginfile/cards.php");
        require_once("{$CFG->dirroot}/course/format/lib.php");

        $course = course_get_format($courseid)->get_course();
        $modinfo = get_fast_modinfo($course);

        $sections = $modinfo->get_section_info_all();
        $numsections = course_get_format($course)->get_last_section_number();

        $cards = [];
        // Mostra as cards.
        foreach ($sections as $section => $thissection) {
            $cardsinfo = $this->show_card($section, $thissection, $numsections, $course, $level = 0);

            if ($cardsinfo) {
                $cards[] = $cardsinfo;
            }
        }

        return $cards;
    }

    /**
     * show_card function
     *
     * @param     $section
     * @param     $thissection
     * @param     $numsections
     * @param     $course
     * @param int $level
     *
     * @return null|array
     * @throws \Exception
     */
    public function show_card($section, $thissection, $numsections, $course, $level = 0) {
        global $COURSE;

        if (!$thissection->visible && !has_capability('moodle/course:sectionvisibility', \context_course::instance($COURSE->id))) {
            return null;
        }

        $showsection = $thissection->uservisible ||
            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
            !$thissection->visible;
        if (!$showsection) {
            return null;
        }

        $thissection->summary = shorten_text($thissection->summary, 150);

        if ($level == 0 && $thissection->parent != 0) {
            return [
                "id" => (int)$thissection->id,
                "section" => $thissection->section,
                "headerdisplaymultipage" => false,
                "parent" => $thissection->parent,
            ];
        }

        if ($numsections !== false && $section > $numsections) {
            return null;
        }

        $this->level = $level;

        return $this->section_header($thissection, $course);
    }

    /**
     * section_header function
     *
     * @param      $section
     * @param      $course
     *
     * @return array
     * @throws \Exception
     */
    protected function section_header($section, $course) {
        global $PAGE;

        $return = [
            "id" => (int)$section->id,
            "section" => $section->section,
            "headerdisplaymultipage" => true,
            "parent" => 0,
        ];

        if ($section->section != 0) {
            $return["headerdisplaymultipage"] = false;
            $return["cardlevel"] = $course->cardlevel;

            $placeholder = $PAGE->theme->image_url('card-thumb', "format_cards");
            $cardsgetimage = cards::get_image($course->id, $section->id);
            $return["image"] = $cardsgetimage ? $cardsgetimage : $placeholder->out();
        }

        return $return;
    }
}
