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
 * service file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [

    "local_kopere_mobile_publicconfig" => [
        "classname" => "\\local_kopere_mobile\\external\\public_config",
        "classpath" => "local/kopere_mobile/classes/external/public_config.php",
        "methodname" => "settings",
        "description" => "Returns a list of the site public settings, those not requiring authentication.",
        "type" => "read",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        "ajax" => true,
        "loginrequired" => false,
    ],

    "format_card_get_structure" => [
        "classname" => "\\local_kopere_mobile\\external\\format_card",
        "classpath" => "local/kopere_mobile/classes/external/format_card.php",
        "methodname" => "get_structure",
        "description" => "Traz a estrutura do Formato Cards para poder renderizar no APP",
        "type" => "read",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_make_view" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_make_view",
        "classpath" => "local/kopere_mobile/classes/external/mod_make_view.php",
        "methodname" => "make_view",
        "description" => "Simulate the mod/view.php web interface page: trigger events, completion, etc...",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_icon" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_icon",
        "classpath" => "local/kopere_mobile/classes/external/mod_icon.php",
        "methodname" => "icon",
        "description" => "Ícones MOD",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_googlemeet_mobile" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_googlemeet",
        "classpath" => "local/kopere_mobile/classes/external/mod_googlemeet.php",
        "methodname" => "mobile",
        "description" => "mod_googlemeet mobile",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_hvp_mobile" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_hvp",
        "classpath" => "local/kopere_mobile/classes/external/mod_hvp.php",
        "methodname" => "mobile",
        "description" => "mod_googlemeet mobile",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_scorm_files" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_scorm",
        "classpath" => "local/kopere_mobile/classes/external/mod_scorm.php",
        "methodname" => "files",
        "description" => "mod_scorm files",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    "local_kopere_mobile_mod_subcourse_mobile" => [
        "classname" => "\\local_kopere_mobile\\external\\mod_subcourse",
        "classpath" => "local/kopere_mobile/classes/external/mod_subcourse.php",
        "methodname" => "mobile",
        "description" => "mod_subcourse mobile",
        "type" => "write",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
