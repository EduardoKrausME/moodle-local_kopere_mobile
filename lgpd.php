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

use local_kopere_dashboard\util\release;

require("../../config.php");
require("../kopere_dashboard/autoload.php");
global $DB, $PAGE, $OUTPUT, $COURSE;

$PAGE->set_context(context_system::instance());

$messagesendok = false;
if (isset($_POST["motivo"][10])) {
    require_sesskey();
    unset($_SESSION["USER"]->sesskey);

    $message = "O aluno solicitou a exclusão dos dados cadastrais do {$COURSE->fullname}\n\n" .
        "Nome completo: " . fullname($USER) . "\n" .
        "Perfil do aluno: {$CFG->wwwroot}/user/profile.php?id={$USER->id} para acesso e exclusão\n" .
        "E-mail cadastrado: {$USER->email}\n" .
        "Motivo da exclusão:\n{$_POST["motivo"]}";

    $userto = (object)[
        "id" => 1,
        "auth" => "OK",
        "suspended" => 0,
        "deleted" => 0,
        "emailstop" => 0,
        "email" => get_config("local_kopere_mobile", "lgpd_email"),
        "username" => "dpo",
        "firstname" => "DPO",
        "lastname" => "LGPD",

        "firstnamephonetic" => "",
        "lastnamephonetic" => "",
        "middlename" => "",
        "alternatename" => "",
    ];

    $eventdata = new \core\message\message();
    if (release::version() >= 3.2) {
        $eventdata->courseid = SITEID;
        $eventdata->modulename = "moodle";
    }
    $eventdata->component = "local_kopere_dashboard";
    $eventdata->name = "kopere_dashboard_messages";
    $eventdata->userfrom = $USER;
    $eventdata->userto = $userto;
    $eventdata->subject = "Solicitação de exclusão de dados";
    $eventdata->fullmessage = $message;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = str_replace("\n", "<br>", $message);
    $eventdata->smallmessage = "";

    message_send($eventdata);

    $messagesendok = true;
}

$PAGE->set_url(new moodle_url("/local/kopere_mobile/lgpd.php"));
$PAGE->set_pagelayout("base");
$PAGE->set_title(get_string("lgpd_title", "local_kopere_mobile"));
$PAGE->set_heading(get_string("lgpd_title", "local_kopere_mobile"));

require_login();

echo $OUTPUT->header();

$lgpdemail = get_config("local_kopere_mobile", "lgpd_email");
if (isset($lgpdemail[5])) {
    if (isset($_POST["motivo"]) && strlen($_POST["motivo"]) < 11) {
        echo "<div class='alert alert-danger'>Motivo é obrigatório</div>";
    }
    if ($messagesendok) {
        echo "<h2>Confirmação de Solicitação de Exclusão de Dados</h2>";
        echo get_config("local_kopere_mobile", "lgpd_okok");
    } else {
        $data = [
            "lgpd_text" => get_config("local_kopere_mobile", "lgpd_text"),
            "user_fullname" => fullname($USER),
            "user_email" => $USER->email,
            "sesskey" => sesskey(),
        ];
        echo $OUTPUT->render_from_template("local_kopere_mobile/lgpd", $data);
    }
} else {
    redirect(
        "{$CFG->wwwroot}/admin/settings.php?section=local_kopere_mobile#id_s_local_kopere_mobile_lgpd_email",
        "Nenhum e-mail cadastrado",
        \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->footer();

/**
 * androidappfile function
 */
function local_kopere_mobile_setting_androidappfile() {
    $context = context_system::instance();
    $fs = get_file_storage();

    $files = $fs->get_area_files($context->id, "local_kopere_mobile", "androidappfile", 0, "filename", false);

    if ($files) {
        /** @var stored_file $file */
        foreach ($files as $file) {
            return moodle_url::make_pluginfile_url($context->id, "local_kopere_mobile", "androidappfile",
                0, "/", $file->get_filename())->out(true);
        }
    }

    return null;
}
