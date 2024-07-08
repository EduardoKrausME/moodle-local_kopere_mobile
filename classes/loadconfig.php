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
 * Class config
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile;

/**
 * Class config
 * @package local_kopere_mobile
 */
class loadconfig {

    /**
     * test function
     *
     * @return object
     *
     * @throws \dml_exception
     */
    public static function test() {
        global $DB, $CFG;

        set_config('typeoflogin', 1, 'tool_mobile');
        set_config('qrcodetype', 0, 'tool_mobile');
        set_config('enablesmartappbanners', 0, 'tool_mobile');
        set_config('forcedurlscheme', 'moodleapp', 'tool_mobile');

        if (get_config('tool_mobile', 'iosappid') == '633359593') {
            set_config('iosappid', '', 'tool_mobile');
        }
        if (get_config('tool_mobile', 'androidappid') == 'com.moodle.moodlemobile') {
            set_config('androidappid', '', 'tool_mobile');
        }

        $setuplink = get_config('tool_mobile', 'setuplink');
        if (strpos($setuplink, 'kopere_mobile') === false) {
            set_config('setuplink', "{$CFG->wwwroot}/local/kopere_mobile/download.php", 'tool_mobile');
        }

        $userreturn = $DB->get_record('user', ['username' => 'usuario-app'],
            'id,auth,confirmed,deleted,suspended,firstname,lastname');

        $externalservicesmoodlemobileapp = $DB->get_field('external_services', 'enabled', ['shortname' => 'moodle_mobile_app']);

        return (object)[
            'is_moodle_cookie_secure' => is_moodle_cookie_secure(),
            'enablemobilewebservice' => $CFG->enablemobilewebservice ? true : false,
            'allowframembedding' => $CFG->allowframembedding ? true : false,
            'external_services_moodle_mobile_app' => $externalservicesmoodlemobileapp ? true : false,
            'is_chrome' => \core_useragent::is_chrome(),
            'check_chrome_version_78' => \core_useragent::check_chrome_version('78'),
            'user' => $userreturn,
            'message_koperemobile_version' => intval(get_config('message_koperemobile', 'version')),
            'local_kopere_mobile_version' => intval(get_config('local_kopere_mobile', 'version')),
        ];
    }

    /**
     * test_to_string function
     *
     * @return null|string|string[]
     *
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function test_to_string() {
        global $CFG, $OUTPUT;

        $returnjson = self::test();
        $returnjson->userapp = "";
        $returnjson->userapp_status = 0;

        if ($returnjson->user) {
            $returnjson->userapp_url = "{$CFG->wwwroot}/user/editadvanced.php?id={$returnjson->user->id}";
            if ($returnjson->user->auth != 'manual') {
                $returnjson->userapp_url = "{$CFG->wwwroot}/user/editadvanced.php?id={$returnjson->user->id}";
                $returnjson->userapp = get_string('config_not_manual', 'local_kopere_mobile');
            } else if ($returnjson->user->confirmed == 0) {
                $returnjson->userapp = get_string('config_not_confirmed', 'local_kopere_mobile');
            } else if ($returnjson->user->deleted == 1) {
                $returnjson->userapp = get_string('config_deleted', 'local_kopere_mobile');
            } else if ($returnjson->user->suspended == 1) {
                $returnjson->userapp = get_string('config_suspended', 'local_kopere_mobile');
            } else {
                $returnjson->userapp_status = 1;
            }
        } else {
            $returnjson->userapp_url = "{$CFG->wwwroot}/user/editadvanced.php?id=-1";
            $returnjson->userapp = get_string('config_not_user', 'local_kopere_mobile');
        }

        $returnjson->show =
            !$returnjson->is_moodle_cookie_secure +
            !$returnjson->allowframembedding +
            !$returnjson->enablemobilewebservice +
            !$returnjson->external_services_moodle_mobile_app +
            $returnjson->userapp_status;

        if ($returnjson->show) {
            $retorno = $OUTPUT->render_from_template('local_kopere_mobile/config_test', $returnjson);
            return preg_replace('/\s+/', ' ', $retorno);
        }
        return "";
    }
}
