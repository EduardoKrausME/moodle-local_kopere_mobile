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

namespace local_kopere_mobile\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/externallib.php");
require_once("$CFG->dirroot/webservice/lib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;
use moodle_exception;
use moodle_url;
use coding_exception;

/**
 * Class config
 *
 * @package local_kopere_mobile\external
 */
class public_config extends external_api {

    /**
     * Returns description of get_settings() parameters.
     *
     * @return external_function_parameters
     */
    public static function settings_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Returns a list of the site public settings, those not requiring authentication.
     *
     * @return array
     * @throws \dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function settings() {
        global $CFG, $SITE, $PAGE;
        require_once($CFG->libdir . '/authlib.php');

        $context = context_system::instance();

        // We need this to make work the format text functions.
        $PAGE->set_context($context);

        // Check if contacting site support is available to all visitors.
        $sitesupportavailable = (isset($CFG->supportavailability) && $CFG->supportavailability == CONTACT_SUPPORT_ANYONE);

        $data= [
            'sitename' => external_format_string($SITE->fullname, $context->id, true),
            'rememberusername' => $CFG->rememberusername,
            'authloginviaemail' => $CFG->authloginviaemail,
            'registerauth' => $CFG->registerauth,
            'forgottenpasswordurl' => clean_param($CFG->forgottenpasswordurl, PARAM_URL), // We may expect a mailto: here.
            'authinstructions' => external_format_text($CFG->auth_instructions, FORMAT_MOODLE, $context->id)[1],
            'maintenanceenabled' => $CFG->maintenance_enabled,
            'maintenancemessage' => external_format_text($CFG->maintenance_message, FORMAT_MOODLE, $context->id)[1],
            'country' => clean_param($CFG->country, PARAM_NOTAGS),
            'autolang' => $CFG->autolang,
            'lang' => clean_param($CFG->lang, PARAM_LANG),  // Avoid breaking WS because of incorrect package langs.
            'langmenu' => $CFG->langmenu,
            'langlist' => $CFG->langlist,
            'locale' => $CFG->locale,
            'supportavailability' => clean_param($CFG->supportavailability, PARAM_INT),
            'supportpage' => $sitesupportavailable ? clean_param($CFG->supportpage, PARAM_URL) : '',
            'customizationapptopo' => self::setting_customizationapptopo(),
            'logologin' => self::setting_logologin(),
            'customizationappcss' => get_config('local_kopere_mobile', 'customizationappcss'),
            'htmllogin' => get_config('local_kopere_mobile', 'htmllogin'),
            'customizationapphome' => get_config('local_kopere_mobile', 'customizationapphome'),
            'customfieldpicture' => json_encode(self::setting_customfieldpicture()),
            'block_myoverview_hidden_course' => json_encode(self::block_myoverview_hidden_course()),

            'message_koperemobile' => get_config('message_koperemobile', 'version') ? true : false,
        ];

        return $data;
    }

    /**
     * Returns description of get_settings() result value.
     */
    public static function settings_returns() {
        return new external_single_structure([
            'sitename' => new external_value(PARAM_RAW, 'Site name.'),
            'rememberusername' => new external_value(PARAM_INT, 'Values: 0 for No, 1 for Yes, 2 for optional.'),
            'authloginviaemail' => new external_value(PARAM_INT, 'Whether log in via email is enabled.'),
            'registerauth' => new external_value(PARAM_PLUGIN, 'Authentication method for user registration.'),
            'forgottenpasswordurl' => new external_value(PARAM_URL, 'Forgotten password URL.'),
            'authinstructions' => new external_value(PARAM_RAW, 'Authentication instructions.'),
            'maintenanceenabled' => new external_value(PARAM_INT, 'Whether site maintenance is enabled.'),
            'maintenancemessage' => new external_value(PARAM_RAW, 'Maintenance message.'),
            'country' => new external_value(PARAM_NOTAGS, 'Default site country', VALUE_OPTIONAL),
            'supportpage' => new external_value(PARAM_URL, 'Site support page link.', VALUE_OPTIONAL),
            'supportavailability' => new external_value(PARAM_INT,
                'Determines who has access to contact site support.', VALUE_OPTIONAL),
            'autolang' => new external_value(PARAM_INT,
                'Whether to detect default language from browser setting.', VALUE_OPTIONAL),
            'lang' => new external_value(PARAM_LANG, 'Default language for the site.', VALUE_OPTIONAL),
            'langmenu' => new external_value(PARAM_INT, 'Whether the language menu should be displayed.', VALUE_OPTIONAL),
            'langlist' => new external_value(PARAM_RAW, 'Languages on language menu.', VALUE_OPTIONAL),
            'locale' => new external_value(PARAM_RAW, 'Sitewide locale.', VALUE_OPTIONAL),

            'customizationapptopo' => new external_value(PARAM_RAW, 'Customization app topo.', VALUE_OPTIONAL),
            'logologin' => new external_value(PARAM_RAW, 'The site logo URL', VALUE_OPTIONAL),
            'customizationappcss' => new external_value(PARAM_RAW, 'Customization app CSS.', VALUE_OPTIONAL),
            'htmllogin' => new external_value(PARAM_RAW, 'Customization APP LOGIN.', VALUE_OPTIONAL),
            'customizationapphome' => new external_value(PARAM_RAW, 'Customization app HOME.', VALUE_OPTIONAL),
            'customfieldpicture' => new external_value(PARAM_RAW, 'Images to icon course', VALUE_OPTIONAL),
            'block_myoverview_hidden_course' => new external_value(PARAM_RAW, 'Block myoverview hidden course', VALUE_OPTIONAL),

            "message_koperemobile" => new external_value(PARAM_INT, 'koperemobile message instaled', VALUE_OPTIONAL),
        ]);
    }

    /**
     * setting_customizationapptopo function
     *
     * @return string
     * @throws \dml_exception
     */
    public static function setting_customizationapptopo() {
        global $CFG;

        $customizationapptopo = get_config('local_kopere_mobile', 'customizationapptopo');
        if ($customizationapptopo) {
            $syscontext = context_system::instance();
            $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                "/{$syscontext->id}/local_kopere_mobile/customizationapptopo/0{$customizationapptopo}");

            return $url->out(false);
        }
        return "";
    }

    /**
     * setting_logologin function
     *
     * @return string
     * @throws \dml_exception
     */
    public static function setting_logologin() {
        global $CFG;

        $logologin = get_config('local_kopere_mobile', 'logologin');
        if ($logologin) {
            $syscontext = context_system::instance();
            $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                "/{$syscontext->id}/local_kopere_mobile/logologin/0{$logologin}");

            return $url->out(false);
        }
        return "";
    }

    /**
     * setting_customfieldpicture function
     *
     * @throws \dml_exception
     */
    public static function setting_customfieldpicture() {
        global $DB;

        $sql = "SELECT f.contenthash, f.filename, cd.id AS data_id, cd.instanceid AS couse_id, cd.contextid
                 FROM {files} f
                 JOIN {customfield_data} cd ON cd.id = f.itemid
                WHERE f.component = 'customfield_picture'
                  AND f.filesize > 10";
        $files = $DB->get_records_sql($sql);

        $images = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($file->contextid, 'customfield_picture', 'file',
                $file->data_id, "/", $file->filename)->out(true);
            $images[$file->couse_id] = (object)[
                'src' => $url,
                'extension' => pathinfo($file->filename, PATHINFO_EXTENSION),
            ];
        }

        return $images;
    }

    /**
     * Function block_myoverview_hidden_course
     *
     * @return array
     * @throws coding_exception
     */
    public static function block_myoverview_hidden_course() {
        global $USER;

        $preferences = get_user_preferences(null, null, $USER);
        $ids = [];
        foreach ($preferences as $key => $value) {
            if (preg_match('/block_myoverview_hidden_course_(\d)+/', $key)) {
                $id = preg_split('/block_myoverview_hidden_course_/', $key);
                $ids[$id[1]] = $id[1];
            }
        }

        return $ids;
    }
}
