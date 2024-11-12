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
 * LGPD file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\message\message;
use local_kopere_dashboard\util\release;

require('../../config.php');
require('../kopere_dashboard/autoload.php');
global $DB, $PAGE, $OUTPUT, $COURSE;

$PAGE->set_context(context_system::instance());

$messagesendok = false;
if (isset($_POST['motivo'][10])) {
    require_sesskey();
    unset($_SESSION['USER']->sesskey);
    $motivo = required_param("motivo", PARAM_TEXT);

    $mensage = get_string("lgpd-body", "local_kopere_mobile", [
        "wwwroot" => $CFG->wwwroot,
        "course_fullname" => $COURSE->fullname,
        "user_id" => $USER->id,
        "user_fullname" => fullname($USER),
        "user_email" => $USER->email,
        "motivo" => $motivo,
    ]);

    $userto = (object)[
        'id' => 1,
        'auth' => 'OK',
        'suspended' => 0,
        'deleted' => 0,
        'emailstop' => 0,
        'email' => get_config('local_kopere_mobile', 'lgpd_email'),
        'username' => 'dpo',
        'firstname' => get_string("lgpd-firstname", "local_kopere_mobile"),
        'lastname' => get_string("lgpd-lastname", "local_kopere_mobile"),

        'firstnamephonetic' => '',
        'lastnamephonetic' => '',
        'middlename' => '',
        'alternatename' => '',
    ];

    $eventdata = new message();
    if (release::version() >= 3.2) {
        $eventdata->courseid = SITEID;
        $eventdata->modulename = 'moodle';
    }
    $eventdata->component = 'local_kopere_dashboard';
    $eventdata->name = 'kopere_dashboard_messages';
    $eventdata->userfrom = $USER;
    $eventdata->userto = $userto;
    $eventdata->subject = get_string('lgpd-subject', "local_kopere_mobile");
    $eventdata->fullmessage = $mensage;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = str_replace("\n", '<br>', $mensage);
    $eventdata->smallmessage = '';

    message_send($eventdata);

    $messagesendok = true;
}

$PAGE->set_url(new moodle_url("/local/kopere_mobile/lgpd.php"));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('lgpd_title', 'local_kopere_mobile'));
$PAGE->set_heading(get_string('lgpd_title', 'local_kopere_mobile'));

require_login();

echo $OUTPUT->header();

$lgpdemail = get_config('local_kopere_mobile', 'lgpd_email');
if (isset($lgpdemail[5])) {
    if (isset($_POST['motivo']) && strlen($_POST['motivo']) < 11) {
        echo get_string("lgpd-reason-required", "local_kopere_mobile");
    }
    if ($messagesendok) {
        echo get_string("lgpd-confirm", "local_kopere_mobile");
        echo get_config('local_kopere_mobile', 'lgpd_okok');
    } else {
        $data = [
            'lgpd_text' => get_config('local_kopere_mobile', 'lgpd_text'),
            'user_fullname' => fullname($USER),
            'user_email' => $USER->email,
        ];
        echo $OUTPUT->render_from_template('local_kopere_mobile/lgpd', $data);
    }
} else {
    redirect(
        "{$CFG->wwwroot}/admin/settings.php?section=local_kopere_mobile#id_s_local_kopere_mobile_lgpd_email",
        get_string("lgpd-nonemail", "local_kopere_mobile"),
        \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->footer();

/**
 * androidappfile function
 */
function local_kopere_mobile_setting_androidappfile() {
    $context = context_system::instance();
    $fs = get_file_storage();

    $files = $fs->get_area_files($context->id, 'local_kopere_mobile', 'androidappfile', 0, 'filename', false);

    if ($files) {
        /** @var stored_file $file */
        foreach ($files as $file) {
            return moodle_url::make_pluginfile_url($context->id, 'local_kopere_mobile', 'androidappfile',
                0, '/', $file->get_filename())->out(true);
        }
    }

    return null;
}
