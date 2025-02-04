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
 * Class injector
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_mobile;

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . "/../lib.php");

/**
 * Class core_hook_output
 *
 * @package local_kopere_mobile
 */
class core_hook_output {

    /**
     * Function before_http_headers
     *
     */
    public static function before_http_headers() {
        global $SESSION, $PAGE;

        $iskoperemobilemobile = isset($SESSION->kopere_mobile_mobile) && $SESSION->kopere_mobile_mobile;
        if ($iskoperemobilemobile || optional_param("kopere_mobile_mobile", false, PARAM_INT)) {

            $PAGE->set_pagelayout("embedded");
            $PAGE->requires->css("/local/kopere_bi/assets/embedded.css");
            if ($PAGE->theme->name == "edooc") {
                $PAGE->requires->css("/local/kopere_bi/assets/edooc-embedded.css");
            }
        }
    }

    /**
     * Function before_standard_head_html_generation
     *
     * @return string
     */
    public static function before_standard_head_html_generation() {
        global $SESSION, $PAGE;

        ob_start();

        if (isset($SESSION->local_kopere_mobile_preserve_page) && $SESSION->local_kopere_mobile_preserve_page) {
            $preservepage = $SESSION->local_kopere_mobile_preserve_page;

            if (strpos($_SERVER["REQUEST_URI"], $preservepage) !== false) { //phpcs:disable
                // Não faz nada aqui.
            } else if (isset($SESSION->kopere_mobile_redirect_page[5])) {
                header("Location: {$SESSION->kopere_mobile_redirect_page}");
                header("kopere_mobile-status: event_observers::process_event");
                die;
            }
        }

        if (isset($SESSION->kopere_mobile_mobile) && $SESSION->kopere_mobile_mobile) {
            $PAGE->set_pagelayout("embedded");
            $return = "
            <meta http-equiv=\"Content-Security-Policy\"
                  content=\"default-src *;
                           style-src  * 'self' 'unsafe-inline' 'unsafe-eval';
                           script-src * 'self' 'unsafe-inline' 'unsafe-eval';
                           font-src   * 'self' data:;\">
            <script>
                window.open = function(url) {
                    location.href = url
                }
                setTimeout(function() {
                    window.open = function(url) {
                        location.href = url
                    }
                }, 1000);
            </script>";

            return $return;
        }
        return "";
    }

    /**
     * Function before_footer_html_generation
     *
     */
    public static function before_footer_html_generation() {
        global $CFG;

        $openedin = optional_param("openedin", false, PARAM_TEXT);
        if ($openedin == "AppMoodleMobileV2" || strpos($_SERVER["HTTP_USER_AGENT"], "AppMoodleMobileV2")) {

            if (strpos($_SERVER["REQUEST_URI"], 'mod/scorm/player.php') > 0 ||
                strpos($_SERVER["REQUEST_URI"], 'local/kopere_mobile/scorm/player.php') > 0) {

                $html = ob_get_contents();
                ob_clean();

                $html = str_replace("www.googletagmanager.com", "", $html);
                $html = str_replace("vlibras.gov.br", "", $html);

                echo $html;
                echo "\n\n\n<script></script>\n";
                echo "<link rel='stylesheet' href='{$CFG->wwwroot}/local/kopere_mobile/scorm/scorm.css'/>";
            }
        }
    }
}

