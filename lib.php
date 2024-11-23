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

use local_kopere_mobile\injector;

/**
 * local_kopere_mobile_before_standard_html_head function
 *
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_kopere_mobile_before_standard_html_head() {
    injector::before_standard_head_html_generation();
}

/**
 *
 */
function local_kopere_mobile_before_http_headers() {
    injector::before_http_headers();
}

/**
 * local_kopere_mobile_before_footer function
 *
 * @throws coding_exception
 */
function local_kopere_mobile_before_footer() {
    injector::before_footer_html_generation();
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
    local_kopere_mobile_pluginfile_send_stored_file($file, 0, 0, false, $options);

    return true;
}
