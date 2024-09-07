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
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * local_kopere_mobile_before_standard_html_head function
 *
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_kopere_mobile_before_standard_html_head() {
    global $SESSION, $PAGE;

    ob_start();

    $PAGE->requires->js_call_amd('local_kopere_mobile/picture', 'move');

    if (isset($SESSION->kopere_mobile_preserve_page) && $SESSION->kopere_mobile_preserve_page) {
        $preservepage = $SESSION->kopere_mobile_preserve_page;

        if (strpos($_SERVER['REQUEST_URI'], $preservepage) !== false) {
            // Não faz nada aqui.
        } else if (isset($SESSION->kopere_mobile_redirect_page[5])) {
            header("Location: {$SESSION->kopere_mobile_redirect_page}");
            header("kopere_mobile-status: event_observers::process_event");
            die();
        }
    }

    if (isset($SESSION->kopere_mobile_mobile) && $SESSION->kopere_mobile_mobile) {
        $PAGE->set_pagelayout('embedded');
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
    global $SESSION, $PAGE;

    $iskoperemobilemobile = isset($SESSION->kopere_mobile_mobile) && $SESSION->kopere_mobile_mobile;
    if ($iskoperemobilemobile || optional_param("kopere_mobile_mobile", false, PARAM_INT)) {

        $PAGE->set_pagelayout('embedded');
        $PAGE->requires->css("/local/kopere_bi/assets/embedded.css");
        if ($PAGE->theme->name == "edooc") {
            $PAGE->requires->css("/local/kopere_bi/assets/edooc-embedded.css");
        }
    }
}

/**
 * local_kopere_mobile_before_footer function
 *
 * @throws coding_exception
 */
function local_kopere_mobile_before_footer() {
    global $CFG;

    $openedin = optional_param("openedin", false, PARAM_TEXT);
    if ($openedin == 'AppMoodleMobileV2' || strpos($_SERVER['HTTP_USER_AGENT'], "AppMoodleMobileV2")) {

        if (strpos($_SERVER['REQUEST_URI'], 'mod/scorm/player.php') > 0 ||
            strpos($_SERVER['REQUEST_URI'], 'local/kopere_mobile/scorm/player.php') > 0) {

            $html = ob_get_contents();
            ob_clean();

            $html = str_replace("www.googletagmanager.com", "", $html);
            $html = str_replace("vlibras.gov.br", "", $html);

            echo $html;
            echo "\n\n\n<script></script>\n";
            echo "<link rel='stylesheet' href='{$CFG->wwwroot}/local/kopere_mobile/scorm/scorm.css'/>";
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
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @throws Exception
 */
function local_kopere_mobile_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;

    if ($filearea == 'androidappfile') {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    } else if ($filearea == 'customizationapptopo') {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    } else if ($filearea == 'logologin') {
        return local_kopere_mobile_pluginfile_sendfile($context, $filearea, $args, $options);
    }

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_once(__DIR__ . "/scorm/lib.php");

    require_login($course, true, $cm);

    $canmanageactivity = has_capability('moodle/course:manageactivities', $context);
    $lifetime = null;

    // Check SCORM availability.
    if (!$canmanageactivity) {
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        $scorm = $DB->get_record('scorm', ['id' => $cm->instance], 'id, timeopen, timeclose', MUST_EXIST);
        list($available, $warnings) = scorm_get_availability_status($scorm);
        if (!$available) {
            return false;
        }
    }

    if ($filearea === 'content') {
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_scorm/content/0/$relativepath";
        $options['immutable'] = true; // Add immutable option, $relativepath changes on file update.

    } else if ($filearea === 'package') {
        // Check if the global setting for disabling package downloads is enabled.
        $protectpackagedownloads = get_config('scorm', 'protectpackagedownloads');
        if ($protectpackagedownloads && !$canmanageactivity) {
            return false;
        }
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_scorm/package/0/$relativepath";
        $lifetime = 0; // No caching here.

    } else if ($filearea === 'imsmanifest') { // This isn't a real filearea, it's a url parameter for this type of package.
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);

        // Get imsmanifest file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_scorm', 'package', 0, '', false);
        $file = reset($files);

        // Check that the package file is an imsmanifest.xml file - if not then this method is not allowed.
        $packagefilename = $file->get_filename();
        if (strtolower($packagefilename) !== 'imsmanifest.xml') {
            return false;
        }

        $file->send_relative_file($relativepath);
    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
        if ($filearea === 'content') { // Return file not found straight away to improve performance.
            send_header_404();
            die;
        }
        return false;
    }

    // Allow SVG files to be loaded within SCORM content, instead of forcing download.
    $options['dontforcesvgdownload'] = true;

    header("kraus-filearea: " . $filearea);
    $pasta = implode("/", $args);
    if (strpos($pasta, ".html")) {
        localscorm_send_stored_file($file, $lifetime, $options);

        global $plugins;
        require_once("{$CFG->dirroot}/lib/jquery/plugins.php");

        echo "
            <script src='{$CFG->wwwroot}/lib/jquery/{$plugins['jquery']['files'][0]}'></script>
            <script src='{$CFG->wwwroot}/local/kopere_mobile/scorm/pdf/scorm-link.js'></script>
            <script src='{$CFG->wwwroot}/local/kopere_mobile/scorm/pdf/modal.js'></script>
            <link  href='{$CFG->wwwroot}/local/kopere_mobile/scorm/pdf/modal.css' rel='stylesheet'/>";
        die();
    }

    // Finally send the file.
    send_stored_file($file, $lifetime, 0, false, $options);
    return true;
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
    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/local_kopere_mobile/{$filearea}/{$relativepath}";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    if ($preview = optional_param("preview", 0, PARAM_INT)) {
        $options['preview'] = $preview;
    }

    require_once(__DIR__ . "/pluginfile_filelib.php");
    localpluginfile_send_stored_file($file, 0, 0, false, $options);

    return true;
}
