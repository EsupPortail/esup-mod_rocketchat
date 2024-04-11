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
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
// Make sure core is loaded.

// Redefine the RC admin menu entry to be expandable.
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

$ADMIN->add('modrocketchatfolder', new admin_externalpage(
        'mod_rocketchat_admin_interface',
        get_string('pluginname_admin', 'mod_rocketchat'),
        "$CFG->wwwroot/mod/rocketchat/management.php",
        'moodle/site:config')
);
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
            'mod_rocketchat/apitoken',
            get_string('apitoken', 'mod_rocketchat'),
            get_string('apitoken_desc', 'mod_rocketchat'),
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

    $settings->add(
        new admin_setting_configcheckbox(
            'mod_rocketchat/retentionfeature',
            get_string('retentionfeature', 'mod_rocketchat'),
            get_string('retentionfeature_desc', 'mod_rocketchat'),
            0
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'mod_rocketchat/retentionenabled',
            get_string('retentionenabled', 'mod_rocketchat'),
            get_string('retentionenabled_desc', 'mod_rocketchat'),
            0
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'mod_rocketchat/maxage',
            get_string('maxage', 'mod_rocketchat'),
            get_string('maxage_desc', 'mod_rocketchat'),
            90
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'mod_rocketchat/maxage_limit',
            get_string('maxage_limit', 'mod_rocketchat'),
            get_string('maxage_limit_desc', 'mod_rocketchat'),
            350
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'mod_rocketchat/filesonly',
            get_string('filesonly', 'mod_rocketchat'),
            get_string('filesonly_desc', 'mod_rocketchat'),
            0
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'mod_rocketchat/excludepinned',
            get_string('excludepinned', 'mod_rocketchat'),
            get_string('excludepinned_desc', 'mod_rocketchat'),
            0
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
    $authplugins = uu_supported_auths();
    $settings->add(
        new admin_setting_configmultiselect( 'mod_rocketchat/user_creation_adv_options_auth_methods',
            get_string('user_creation_adv_options_auth_methods', 'mod_rocketchat'),
            get_string('user_creation_adv_options_auth_methods_desc', 'mod_rocketchat'),
            array(),
            $authplugins
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/user_creation_adv_options_requirePasswordChange',
            get_string('user_creation_adv_options_requirePasswordChange', 'mod_rocketchat'),
            get_string('user_creation_adv_options_requirePasswordChange_desc', 'mod_rocketchat'),
            0
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/user_creation_adv_options_setRandomPassword',
            get_string('user_creation_adv_options_setRandomPassword', 'mod_rocketchat'),
            get_string('user_creation_adv_options_setRandomPassword_desc', 'mod_rocketchat'),
            0
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/user_creation_adv_options_sendWelcomeEmail',
            get_string('user_creation_adv_options_sendWelcomeEmail', 'mod_rocketchat'),
            get_string('user_creation_adv_options_sendWelcomeEmail_desc', 'mod_rocketchat'),
            0
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('mod_rocketchat/user_creation_adv_options_verify',
            get_string('user_creation_adv_options_verify', 'mod_rocketchat'),
            get_string('user_creation_adv_options_verify_desc', 'mod_rocketchat'),
            0
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
        new admin_setting_configtextarea('mod_rocketchat/replacementgroupnamecharacters',
            get_string('replacementgroupnamecharacters', 'mod_rocketchat'),
            get_string('replacementgroupnamecharacters_desc', 'mod_rocketchat'),
            ''
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
        new admin_setting_configcheckbox('mod_rocketchat/usernamehook',
            get_string('usernamehook', 'mod_rocketchat'),
            get_string('usernamehook_desc', 'mod_rocketchat'),
            0
        )
    );

    $enabledenrolmentplugins = enrol_get_plugins(true);
    $enabledenrolmentplugins = array_keys($enabledenrolmentplugins);
    array_walk($enabledenrolmentplugins,
        function(&$value, $key){
            $value = 'enrol_'.$value;
        }
    );
    $enabledenrolmentplugins = array_combine($enabledenrolmentplugins, $enabledenrolmentplugins);
    $default = array(
        'enrol_flatfile' => 'enrol_flatfile',
        'enrol_cohort' => 'enrol_cohort'
    );
    $settings->add(
        new admin_setting_configmultiselect('mod_rocketchat/background_enrolment_task',
            get_string('background_enrolment_task', 'mod_rocketchat'),
            get_string('background_enrolment_task_desc', 'mod_rocketchat'),
            $default,
            $enabledenrolmentplugins
        )
    );
    $settings->add(new admin_setting_configcheckbox(
        'mod_rocketchat/background_add_instance', get_string('background_add_instance', 'mod_rocketchat'),
        get_string('background_add_instance_desc', 'mod_rocketchat'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_rocketchat/background_restore', get_string('background_restore', 'mod_rocketchat'),
        get_string('background_restore_desc', 'mod_rocketchat'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_rocketchat/background_synchronize', get_string('background_synchronize', 'mod_rocketchat'),
        get_string('background_synchronize_desc', 'mod_rocketchat'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_rocketchat/background_user_update', get_string('background_user_update', 'mod_rocketchat'),
        get_string('background_user_update_desc', 'mod_rocketchat'),
        1
    ));
}
// Prevent Moodle from adding settings block in standard location.
$settings = null;
