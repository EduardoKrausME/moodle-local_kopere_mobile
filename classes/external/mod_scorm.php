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

require_once("{$CFG->libdir}/externallib.php");

/**
 * Class mod_icon
 *
 * @package local_kopere_mobile\external
 */
class mod_scorm extends \external_api {

    /**
     * files_parameters function
     *
     * @return \external_function_parameters
     */
    public static function files_parameters() {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'mod instance id'),
        ]);
    }

    /**
     * icon function
     *
     * @param $cmid
     * @return array
     * @throws \dml_exception
     */
    public static function icon($cmid) {
        global $DB, $CFG;

        $context = \context_module::instance($cmid);

        $sql = "SELECT filepath, filename, filesize, itemid, mimetype
                  FROM {files} 
                 WHERE component = 'mod_scorm'
                   AND filearea  = 'content'
                   AND filesize  > 1
                   AND contextid = {$context->id}";
        $files = $DB->get_records_sql($sql);

        $returnfiles = [];
        foreach ($files as $file) {
                $returnfiles[] = [
                    "filepath" => $file->filepath,
                    "filename" => $file->filename,
                    "filesize" => $file->filesize,
                    "fileurl" => "{$CFG->wwwroot}/pluginfile.php/{$context->id}/mod_scorm/content/{$file->itemid}{$file->filepath}{$file->filename}",
                ];
        }

        return $returnfiles;
    }

    /**
     * files_returns function
     *
     * @return \external_description
     */
    public static function files_returns() {
        return new \external_multiple_structure(new \external_single_structure([
            'filepath' => new \external_value(PARAM_RAW, ''),
            'filename' => new \external_value(PARAM_RAW, ''),
            'filesize' => new \external_value(PARAM_INT, ''),
            'fileurl' => new \external_value(PARAM_RAW, ''),
        ]));
    }
}
