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
 * lib file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * local_kopere_mobile_before_standard_html_head function
 *
 * @return string
 * @throws Exception
 */
function local_kopere_mobile_before_standard_html_head() {
    global $USER, $PAGE;

    ob_start();

    $PAGE->requires->js_call_amd("local_kopere_mobile/picture", "move");

    if (isset($USER->local_kopere_mobile_preserve_page) && $USER->local_kopere_mobile_preserve_page) {
        if (isset($USER->kopere_mobile_redirect_page[5])) {
            header("Location: {$USER->kopere_mobile_redirect_page}");
            header("kopere_mobile-status: event_observers::process_event");
            die;
        }
    }

    if (isset($USER->local_kopere_mobile_mobile) && $USER->local_kopere_mobile_mobile) {
        $PAGE->set_pagelayout("embedded");
        $return = "
            <meta http-equiv=\"Content-Security-Policy\"
                  content=\"default-src *;
                           style-src  * 'self' 'unsafe-inline' 'unsafe-eval';
                           script-src * 'self' 'unsafe-inline' 'unsafe-eval';
                           font-src   * 'self' data:;\">
            <script>
                window.open = function(url) {
                    location.href = url
                }
                setTimeout(function() {
                    window.open = function(url) {
                        location.href = url
                    }
                }, 1000);
            </script>";

        return $return;
    }
    return "";
}

/**
 *
 */
function local_kopere_mobile_before_http_headers() {
    global $USER, $PAGE;

    $iskoperemobilemobile = isset($USER->local_kopere_mobile_mobile) && $USER->local_kopere_mobile_mobile;
    if ($iskoperemobilemobile || optional_param("local_kopere_mobile_mobile", false, PARAM_INT)) {

        $PAGE->set_pagelayout("embedded");
        $PAGE->requires->css("/local/kopere_bi/assets/embedded.css");
        if ($PAGE->theme->name == "edooc") {
            $PAGE->requires->css("/local/kopere_bi/assets/edooc-embedded.css");
        }
    }
}

/**
 * Serves KopereMobile content
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 *
 * @return bool
 * @throws Exception
 */
function local_kopere_mobile_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($filearea == "androidappfile") {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    } else if ($filearea == "customizationapptopo") {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    } else if ($filearea == "logologin") {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    }
}

/**
 * APK File serving.
 *
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args      List of arguments.
 * @param array $options   Array of options.
 *
 * @return bool
 * @throws Exception
 */
function local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, array $options = []) {

    $fs = get_file_storage();
    $relativepath = implode("/", $args);

    $fullpath = "/{$context->id}/local_kopere_mobile/{$filearea}/{$relativepath}";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    if ($preview = optional_param("preview", 0, PARAM_INT)) {
        $options["preview"] = $preview;
    }

    require_once(__DIR__ . "/pluginfile_filelib.php");
    localpluginfile_send_stored_file($file, 0, 0, false, $options);

    return true;
}
