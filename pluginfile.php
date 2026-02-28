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
 * pluginfile file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * NO_MOODLE_COOKIES - we don't want any cookie
 */
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . "/../../config.php");
require_once("{$CFG->libdir}/filelib.php");
require_once("{$CFG->dirroot}/webservice/lib.php");

// Allow CORS requests.
header_remove("Access-Control-Allow-Origin");
header("Access-Control-Allow-Origin: *");

// Use preview in order to display the preview of the file (e.g. "thumb" for a thumbnail).
$preview = optional_param("preview", null, PARAM_ALPHANUM);

// Offline means download the file from the repository and serve it, even if it was an external link.
// The repository may have to export the file to an offline format.
$offline = optional_param("offline", 0, PARAM_BOOL);

// Authenticate the user.
$token = optional_param("token", false, PARAM_ALPHANUM);
if ($token) {
    $webservicelib = new webservice();
    $authenticationinfo = $webservicelib->authenticate_user($token);
}

// Finally we can serve the file :).
$relativepath = get_file_argument();

require_once(__DIR__ . "/pluginfile_filelib.php");

localpluginfile_file_pluginfile($relativepath, 0, $preview, $offline);
