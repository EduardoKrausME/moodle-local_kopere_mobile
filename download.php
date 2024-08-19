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
 * download file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require('../kopere_dashboard/autoload.php');
require($CFG->libdir . '/adminlib.php');
global $DB, $PAGE, $OUTPUT;

$PAGE->set_context(null);

$PAGE->set_url(new moodle_url("/local/kopere_mobile/download.php"));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('download_title', 'local_kopere_mobile'));
$PAGE->set_heading(get_string('download_title', 'local_kopere_mobile'));

echo $OUTPUT->header();

echo get_string('download_info', 'local_kopere_mobile');

if ($iosappid = get_config('local_kopere_mobile', 'iosappid')) {
    if (get_component_version('core') < 2017051509) {
        $loaderimgurl = $OUTPUT->pix_url('app-store', 'local_kopere_mobile');
    } else {
        $loaderimgurl = $OUTPUT->image_url('app-store', 'local_kopere_mobile');
    }
    echo "<a href='https://itunes.apple.com/app/{$iosappid}' target='_blank'>
              <img height='50' src='{$loaderimgurl}' alt='Download from iOS App Store'>
          </a>";
}

if ($androidappid = get_config('local_kopere_mobile', 'androidappid')) {
    if (get_component_version('core') < 2017051509) {
        $loaderimgurl = $OUTPUT->pix_url('google-play', 'local_kopere_mobile');
    } else {
        $loaderimgurl = $OUTPUT->image_url('google-play', 'local_kopere_mobile');
    }
    echo "<a href='https://play.google.com/store/apps/details?id={$androidappid}' target='_blank'>
              <img height='50' src='{$loaderimgurl}' alt='Download from Google play'>
          </a>";
}

if ($androidappfile = local_kopere_mobile_setting_androidappfile()) {
    if (get_component_version('core') < 2017051509) {
        $loaderimgurl = $OUTPUT->pix_url('download', 'local_kopere_mobile');
    } else {
        $loaderimgurl = $OUTPUT->image_url('download', 'local_kopere_mobile');
    }
    echo "<a href='{$androidappfile}' target='_blank'>
              <img height='50' src='{$loaderimgurl}' alt='Download from Google play'>
          </a>";
}

echo $OUTPUT->footer();

/**
 * local_kopere_mobile_setting_androidappfile function
 */
function local_kopere_mobile_setting_androidappfile() {
    $context = context_system::instance();
    $fs = get_file_storage();

    $files = $fs->get_area_files($context->id, 'local_kopere_mobile', 'androidappfile', 0, 'filename', false);

    if ($files) {
        /** @var stored_file $file */
        foreach ($files as $file) {
            return moodle_url::make_pluginfile_url($context->id, 'local_kopere_mobile', 'androidappfile',
                0, "/", $file->get_filename())->out(true);
        }
    }

    return null;
}
