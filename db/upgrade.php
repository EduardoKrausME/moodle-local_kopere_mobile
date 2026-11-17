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
 * upgrade file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * upgrade function
 *
 * @param $oldversion
 * @return bool
 * @throws Exception
 */
function xmldb_local_kopere_mobile_upgrade($oldversion) {
    if ($oldversion < 2024020400) {
        set_config("lgpd_text", get_string("lgpd_text_msgdefault", "local_kopere_mobile"), "local_kopere_mobile");
        set_config("lgpd_okok", get_string("lgpd_okok_msgdefault", "local_kopere_mobile"), "local_kopere_mobile");

        upgrade_plugin_savepoint(true, 2024020400, "local", "kopere_mobile");
    }
    if ($oldversion < 2026111700) {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_kopere_mobile_links');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('jti', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('target', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('expires', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('jtiunique', XMLDB_INDEX_UNIQUE, ['jti']);
            $table->add_index('expires', XMLDB_INDEX_NOTUNIQUE, ['expires']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111700, 'local', 'kopere_mobile');
    }
    return true;
}
