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
 * Class mod_icon
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
 * Class mod_icon
 * @package local_kopere_mobile\external
 */
class mod_icon extends \external_api {

    /**
     * icon_parameters function
     *
     * @return \external_function_parameters
     */
    public static function icon_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * icon function
     *
     * @return array
     * @throws \dml_exception
     */
    public static function icon() {
        global $DB, $CFG, $PAGE;

        $themerev = theme_get_revision();
        $modules = $DB->get_records_sql("SELECT id, name FROM {modules} WHERE id IN(SELECT DISTINCT module FROM {course_modules})");

        $returnmodules = [];
        foreach ($modules as $module) {

            $monologoext = false;
            $monologourl = "";
            $iconext = false;
            $iconurl = "";

            if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/monologo.svg")) {
                $monologoext = "svg";
                $monologourl = "{$CFG->wwwroot}/theme/image.php/{$PAGE->theme->name}/{$module->name}/{$themerev}/monologo";
            } else if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/monologo.png")) {
                $monologoext = "png";
                $monologourl = "{$CFG->wwwroot}/theme/image.php/_s/{$PAGE->theme->name}/{$module->name}/{$themerev}/monologo";
            }

            if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/icon.svg")) {
                $iconext = "svg";
                $iconurl = "{$CFG->wwwroot}/theme/image.php/{$PAGE->theme->name}/{$module->name}/{$themerev}/icon";
            } else if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/icon.png")) {
                $iconext = "png";
                $iconurl = "{$CFG->wwwroot}/theme/image.php/_s/{$PAGE->theme->name}/{$module->name}/{$themerev}/icon";
            } else if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/icon.gif")) {
                $iconext = "gif";
                $iconurl = "{$CFG->wwwroot}/theme/image.php/_s/{$PAGE->theme->name}/{$module->name}/{$themerev}/icon";
            } else if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/icon.jpg")) {
                $iconext = "jpg";
                $iconurl = "{$CFG->wwwroot}/theme/image.php/_s/{$PAGE->theme->name}/{$module->name}/{$themerev}/icon";
            } else if (file_exists($icon = "{$CFG->dirroot}/mod/{$module->name}/pix/icon.jpeg")) {
                $iconext = "jpeg";
                $iconurl = "{$CFG->wwwroot}/theme/image.php/_s/{$PAGE->theme->name}/{$module->name}/{$themerev}/icon";
            }

            require_once("{$CFG->dirroot}/mod/{$module->name}/lib.php");
            $function = "{$module->name}_supports";

            $modpurpose = function_exists($function) ? $function("mod_purpose") : "";
            $courses = $DB->get_records_sql("SELECT course FROM {course_modules} WHERE module = {$module->id}");

            $returnmodules[] = [
                "name" => $module->name,
                "monologo_ext" => $monologoext,
                "monologo_url" => $monologourl,
                "icon_ext" => $iconext,
                "icon_url" => $iconurl,
                "mod_purpose" => $modpurpose,
                "courses" => json_encode(array_keys($courses)),
            ];
        }

        return $returnmodules;
    }

    /**
     * icon_returns function
     *
     * @return \external_description
     */
    public static function icon_returns() {
        return new \external_multiple_structure(new \external_single_structure([
            'name' => new \external_value(PARAM_RAW, ''),
            'monologo_ext' => new \external_value(PARAM_RAW, ''),
            'monologo_url' => new \external_value(PARAM_RAW, ''),
            'icon_ext' => new \external_value(PARAM_RAW, ''),
            'icon_url' => new \external_value(PARAM_RAW, ''),
            'mod_purpose' => new \external_value(PARAM_RAW, ''),
            'courses' => new \external_value(PARAM_RAW, ''),
        ]));
    }
}
