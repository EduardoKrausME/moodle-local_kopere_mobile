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
 * Settings file
 *
 * @package    local_kopere_mobile
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_kopere_mobile', get_string('setting_title', 'local_kopere_mobile'));
    $ADMIN->add('localplugins', $settings);

    require_once(__DIR__ . '/classes/config.php');
    $test = \local_kopere_mobile\config::test_to_string();
    if ($test) {
        $setting = new admin_setting_heading('local_kopere_mobile/name',
            get_string('status_app', 'local_kopere_mobile'), $test);
        $settings->add($setting);
    }

    $setting = new admin_setting_heading('local_kopere_mobile/customization',
        get_string('customizationapp', 'local_kopere_mobile'), "");
    $settings->add($setting);


    $setting = new admin_setting_configstoredfile('local_kopere_mobile/logologin',
        get_string('logologin', 'local_kopere_mobile'),
        get_string('logologin_desc', 'local_kopere_mobile'),
        'logologin', 0, ['maxfiles' => 1, 'accepted_types' => ['.png', '.svg']]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);


    $setting = new admin_setting_configstoredfile('local_kopere_mobile/customizationapptopo',
        get_string('customizationapptopo', 'local_kopere_mobile'),
        get_string('customizationapptopo_desc', 'local_kopere_mobile'),
        'customizationapptopo', 0, ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg']]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $extradescription = "";
    if (file_exists("{$CFG->dirroot}/customfield/field/picture/version.php")) {
        $category = $DB->get_record('customfield_category',
            ['id' => intval(@$CFG->local_kopere_mobile_customfield_picture)]);
        if (!$category) {
            $category = (object)[
                'name' => 'Moodle APP',
                'description' => null,
                'descriptionformat' => '0',
                'sortorder' => '0',
                'timecreated' => time(),
                'timemodified' => time(),
                'component' => 'core_course',
                'area' => 'course',
                'itemid' => '0',
                'contextid' => context_system::instance()->id,
            ];
            $category->id = $DB->insert_record('customfield_category', $category);
            $CFG->local_kopere_mobile_customfield_picture = $category->id;
            set_config('local_kopere_mobile_customfield_picture', $category->id);
        }
        $field = $DB->get_record('customfield_field', ['shortname' => 'app_background']);
        if (!$field) {
            $field = [
                'shortname' => 'app_background',
                'name' => 'Imagem de fundo do APP',
                'description' => "Esta imagem será utilizada como plano de fundo da lista de cursos aplicativo, " .
                    "com dimensões específicas de 600 x 300 pixel.",
                'type' => 'picture',
                'descriptionformat' => 0,
                'sortorder' => 0,
                'categoryid' => $CFG->local_kopere_mobile_customfield_picture,
                'configdata' => null,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $DB->insert_record('customfield_field', $field);
        }
    } else {
        $extradescription = "<div class='alert alert-warning'>Você precisa instalar o plugin " .
            "<a href='https://moodle.org/plugins/customfield_picture' target='_blank'>customfield_picture</a> " .
            "para poder personalizar a imagem de fundo.</div>";
    }
    $choices = [
        'default' => get_string('customizationapphome_default', 'local_kopere_mobile'),
        'background' => get_string('customizationapphome_background', 'local_kopere_mobile'),
    ];
    $setting = new admin_setting_configselect('local_kopere_mobile/customizationapphome',
        get_string('customizationapphome', 'local_kopere_mobile'),
        get_string('customizationapphome_desc', 'local_kopere_mobile') . $extradescription,
        'default', $choices);
    $settings->add($setting);

    $setting = new admin_setting_configtextarea('local_kopere_mobile/customizationappcss',
        get_string('customizationappcss', 'local_kopere_mobile'),
        get_string('customizationappcss_desc', 'local_kopere_mobile'), '', PARAM_RAW);
    $settings->add($setting);

    $setting = new admin_setting_configtextarea('local_kopere_mobile/htmllogin',
        get_string('htmllogin', 'local_kopere_mobile'),
        get_string('htmllogin_desc', 'local_kopere_mobile'), '', PARAM_RAW);
    $settings->add($setting);

    $setting = new admin_setting_heading('local_kopere_mobile/app', get_string('app_title', 'local_kopere_mobile'), "");
    $settings->add($setting);

    $setting = new admin_setting_configtext('local_kopere_mobile/iosappid',
        get_string('iosappid', 'local_kopere_mobile'),
        get_string('iosappid_desc', 'local_kopere_mobile'), '', PARAM_INT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('local_kopere_mobile/androidappid',
        get_string('androidappid', 'local_kopere_mobile'),
        get_string('androidappid_desc', 'local_kopere_mobile'), '', PARAM_NOTAGS);
    $settings->add($setting);

    $setting = new admin_setting_configstoredfile('local_kopere_mobile/androidappfile',
        get_string('androidappfile', 'local_kopere_mobile'),
        get_string('androidappfile_desc', 'local_kopere_mobile'),
        'androidappfile', 0, ['maxfiles' => 1, 'accepted_types' => ['.apk']]);
    $settings->add($setting);

    $setting = new admin_setting_heading('local_kopere_mobile/lgpd', get_string('lgpd_title', 'local_kopere_mobile'),
        "<a href='{$CFG->wwwroot}/local/kopere_mobile/lgpd.php' target=_blank>{$CFG->wwwroot}/local/kopere_mobile/lgpd.php</a>");
    $settings->add($setting);

    $setting = new admin_setting_configtext('local_kopere_mobile/lgpd_email',
        get_string('lgpd_email', 'local_kopere_mobile'),
        get_string('lgpd_email_desc', 'local_kopere_mobile'), '', PARAM_EMAIL);
    $settings->add($setting);

    $setting = new admin_setting_confightmleditor('local_kopere_mobile/lgpd_text',
        get_string('lgpd_text', 'local_kopere_mobile'),
        get_string('lgpd_text_desc', 'local_kopere_mobile'), '');
    $settings->add($setting);

    $setting = new admin_setting_confightmleditor('local_kopere_mobile/lgpd_okok',
        get_string('lgpd_okok', 'local_kopere_mobile'),
        get_string('lgpd_okok_desc', 'local_kopere_mobile'), '');
    $settings->add($setting);
}
