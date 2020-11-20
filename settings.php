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
 * Plugin administration pages are defined here.
 *
 * @package     mod_rocketchat
 * @category    admin
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');
// Make sure core is loaded.

// Redefine the H5P admin menu entry to be expandable.
$modrocketchatfolder = new admin_category('modrocketchatfolder',
    new lang_string('pluginname', 'mod_rocketchat'),
    $module->is_enabled() === false);
// Add the Settings admin menu entry.
$ADMIN->add('modsettings', $modrocketchatfolder);
$settings->visiblename = new lang_string('settings', 'mod_rocketchat');
// Add the Libraries admin menu entry.
$ADMIN->add('modrocketchatfolder', $settings);
$ADMIN->add('modrocketchatfolder', new admin_externalpage('rocketchatconnectiontest',
    new lang_string('testconnection', 'mod_rocketchat'),
    new moodle_url('/mod/rocketchat/test_connection.php')));

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext(
            'mod_rocketchat/instanceurl',
            get_string('instanceurl', 'mod_rocketchat'),
            get_string('instanceurl_desc', 'mod_rocketchat'),
            null,
            PARAM_URL
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'mod_rocketchat/restapiroot',
            get_string('restapiroot', 'mod_rocketchat'),
            get_string('restapiroot_desc', 'mod_rocketchat'),
            '/api/v1/',
            PARAM_RAW_TRIMMED
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'mod_rocketchat/apiuser',
            get_string('apiuser', 'mod_rocketchat'),
            get_string('apiuser_desc', 'mod_rocketchat'),
            null,
            PARAM_RAW_TRIMMED
        )
    );

    $settings->add(
        new admin_setting_configpasswordunmask(
            'mod_rocketchat/apipassword',
            get_string('apipassword', 'mod_rocketchat'),
            get_string('apipassword_desc', 'mod_rocketchat'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext('mod_rocketchat/groupnametoformat',
            get_string('groupnametoformat', 'mod_rocketchat'),
            get_string('groupnametoformat_desc', 'mod_rocketchat'),
            '{$a->moodleid}_{$a->courseshortname}_{$a->moduleid}'
        )
    );

    $rolesoptions = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);
    $editingteachers = get_archetype_roles('editingteacher');
    $student = get_archetype_roles('student');
    $settings->add(
        new admin_setting_configmultiselect('mod_rocketchat/defaultmoderatorroles',
            get_string('defaultmoderatorroles', 'mod_rocketchat'),
            get_string('defaultmoderatorroles_desc', 'mod_rocketchat'),
            array_keys($editingteachers),
            $rolesoptions
        )
    );

    $settings->add(
        new admin_setting_configmultiselect('mod_rocketchat/defaultuserroles',
            get_string('defaultuserroles', 'mod_rocketchat'),
            get_string('defaultuserroles_desc', 'mod_rocketchat'),
            array_keys($student),
            $rolesoptions
        )
    );

    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/create_user_account_if_not_exists',
            get_string('create_user_account_if_not_exists', 'mod_rocketchat'),
            get_string('create_user_account_if_not_exists_desc', 'mod_rocketchat'),
            1
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/recyclebin_patch',
            get_string('recyclebin_patch', 'mod_rocketchat'),
            get_string('recyclebin_patch_desc', 'mod_rocketchat'),
            1
        )
    );
    $settings->add(
        new admin_setting_configtext('mod_rocketchat/validationgroupnameregex',
            get_string('validationgroupnameregex', 'mod_rocketchat'),
            get_string('validationgroupnameregex_desc', 'mod_rocketchat'),
                '/[^0-9a-zA-Z-_.]/'
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/embedded_display_mode',
            get_string('embedded_display_mode_setting', 'mod_rocketchat'),
            get_string('embedded_display_mode_setting_desc', 'mod_rocketchat'),
            1
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/verbose_mode',
            get_string('verbose_mode', 'mod_rocketchat'),
            get_string('verbose_mode_desc', 'mod_rocketchat'),
            0
        )
    );
}
// Prevent Moodle from adding settings block in standard location.
$settings = null;