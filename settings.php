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
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');
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
    $deletionoptions = mod_rocketchat_tools::get_deletion_options();
    $settings->add(
        new admin_setting_configselect('mod_rocketchat/deletionmode',
            get_string('deletionmode', 'mod_rocketchat'),
            get_string('deletionmode_desc', 'mod_rocketchat'),
            mod_rocketchat_tools::DELETION_ARCHIVE,
            $deletionoptions
        )
    );
}
