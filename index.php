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
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
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
            // Old flow accepted permanent Moodle tokens in URLs and permitted
            // unvalidated redirects. It has been permanently retired.
            http_response_code(410);
            header("Cache-Control: no-store");
            header("Content-Type: text/plain; charset=utf-8");
            die("This link format is no longer supported. Update the mobile app.");
        case "app-branding":
            // Public visual identity is safe for the build panel and mobile clients.
            die(json_encode(\local_kopere_mobile\branding::export(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        case "test-config":

            $returnjson = \local_kopere_mobile\loadconfig::test();

            die(json_encode($returnjson));
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

