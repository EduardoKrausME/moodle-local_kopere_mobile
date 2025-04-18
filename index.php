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
 * index file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No login check is expected here bacause token validation.
// @codingStandardsIgnoreLine
require ("../../config.php");

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-Type: application/json");
header("Expires: 0");

$PAGE->set_context(null);

if (empty($localkoperesendheader)) {
    header_remove("Access-Control-Allow-Origin");
    header("Access-Control-Allow-Origin: *");
}
$localkoperesendheader = true;

try {
    $action = optional_param("action", false, PARAM_TEXT);

    switch ($action) {
        case "loadpage":
            validate_token();

            $redirectpage = optional_param("webpage", false, PARAM_TEXT);

            $mobile = optional_param("local_kopere_mobile_mobile", false, PARAM_INT);
            if ($mobile) {
                $USER->local_kopere_mobile_mobile = 1;
            }
            $preservepage = optional_param("local_kopere_mobile_preserve_page", false, PARAM_TEXT);
            if ($preservepage) {
                $USER->local_kopere_mobile_preserve_page = $preservepage;
                $USER->kopere_mobile_redirect_page = $redirectpage;
            }

            $platform = optional_param("local_kopere_mobile_platform", false, PARAM_TEXT);
            if ($platform) {
                $USER->local_kopere_mobile_platform = $platform;
            }

            $sessionid = session_id();
            header("Set-Cookie: MoodleSession={$sessionid}; path=/; SameSite=None; Secure");
            header("Location: {$redirectpage}");

            die;
            break;

        case "test-config":

            $returnjson = \local_kopere_mobile\loadconfig::test();

            die(json_encode($returnjson));
            break;
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * validate token function
 *
 * @throws coding_exception
 * @throws dml_exception
 */
function validate_token() {
    global $DB, $USER;

    if (isloggedin()) {
        @header("kopere-status:isloggedin");
        return true;
    }

    $token = optional_param("token", false, PARAM_TEXT);
    if (!$token) {
        @header("kopere-status:no-token");
        return false;
    }

    $sessao = $DB->get_record("external_tokens", ["token" => $token]);
    if (!$sessao) {
        @header("kopere-status:no-session");
        return false;
    }

    $user = $DB->get_record("user", ["id" => $sessao->userid]);
    if (!$user) {
        @header("kopere-status:no-user");
        return false;
    }

    \core\session\manager::login_user($user);
    unset($USER->preference);
    check_user_preferences_loaded($USER);

    return true;
}


