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
 * picture.js
 *
 * @package   local_kopere_mobile
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        move : function() {

            if (document.getElementById('fitem_id_customfield_app_background')) {
                if (document.getElementById('fitem_id_overviewfiles_filemanager')) {
                    var app_background = $("#fitem_id_customfield_app_background");
                    var filemanager = $("#fitem_id_overviewfiles_filemanager");

                    var categoryid = app_background.parent().parent().attr("id");

                    app_background.appendTo(filemanager.parent());

                    $("#" + categoryid).remove();
                }
            }
        }
    };
});
