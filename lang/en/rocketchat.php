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
 * Plugin strings are defined here.
 *
 * @package     mod_rocketchat
 * @category    string
 * @author      Celine Pervès <cperves@unistra.fr>
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['description'] = 'Module interfacing Rockat.Chat and Moodle';
$string['modulename'] = 'Rocket.Chat';
$string['modulenameplural'] = 'Rocket.Chat';
$string['pluginname'] = 'Rocket.Chat';
$string['name'] = 'Instance name (in the course)';
$string['instanceurl'] = 'Rocket.Chat instance URL';
$string['instanceurl_desc'] = 'Rocket.Chat instance URL (ex: https://rocketchat.univ.fr)';
$string['restapiroot'] = 'Rocket.Chat REST API root';
$string['restapiroot_desc'] = 'Rocket.Chat REST API root';
$string['tokenmode'] = 'Token mode';
$string['tokenmode_desc'] = 'If token mode is activated apiuser will be a Rocket.Chat id and token will be a valid Rocket.Chat token'
    .' associated to this Rocket.Chat User';
$string['apiuser'] = 'Rocket.Chat API user';
$string['apiuser_desc'] = 'Rocket.Chat API user / Token mode : Rocket.Chat User Id ';
$string['apipassword'] = 'Rocket.Chat API password';
$string['apipassword_desc'] = 'Rocket.Chat API password / Token mode : Rocket.Chat associated token';
$string['norocketchats'] = 'No Rocket.Chat module instances.';
$string['groupnametoformat'] = 'Formatted group name';
$string['groupnametoformat_desc'] = 'String format {$a->parameter} is possible with the following parameters : moodleid, moodleshortname, moodlefullname, moduleid, modulemoodleid (unique whitin all your possible moodle), courseid, courseshortname, coursefullname';
$string['joinrocketchat'] = 'Join Rocket.Chat session';
$string['displaytype'] = 'Display type';
$string['displaynew'] = 'Display in new window';
$string['displaypopup'] = 'Display in popup window';
$string['displaycurrent'] = 'Display in current window';
$string['popupheight'] = 'Pop-up height';
$string['popupwidth'] = 'Pop-up width';
$string['pluginadministration'] = 'Rocket.Chat administration';
$string['defaultmoderatorroles'] = 'Rocket.Chat moderators';
$string['moderatorroles'] = 'Moodle roles in course that will be Rocket.Chat moderators';
$string['defaultmoderatorroles_desc'] = 'Moodle roles in course that will be Rocket.Chat moderators';
$string['defaultuserroles'] = 'Rocket.Chat users.';
$string['userroles'] = 'Moodle roles in course that will be Rocket.Chat users (with normal user rights)';
$string['defaultuserroles_desc'] = 'Moodle roles in course that will be Rocket.Chat users (with normal user rights)';
$string['rocketchat:addinstance'] = 'Add a Rocket.Chat module instance';
$string['rocketchat:view'] = 'View the Rocket.Chat module instances';
$string['rocketchat:candefineroles'] = 'Can define roles to apply in Rocket.Chat\'s private groups';
$string['rocketchat:change_embedded_display_mode'] = 'Can change the display mode (embedded) of each module instance';
$string['rocketchat_nickname'] = '{$a->firstname} {$a->lastname}';
$string['create_user_account_if_not_exists'] = 'Create Rocket.Chat user account';
$string['create_user_account_if_not_exists_desc'] = 'While enrolling user, create Rocket.Chat corresponding user account(username) if not exists';
$string['recyclebin_patch'] = 'Is recyclebin moodle core patch installed?';
$string['recyclebin_patch_desc'] = 'the mod rocketchat recyclebin patch is a patch locate in admin/tool/recyclebin/classes/course_bin.php file enabling to pass cmid and module instanceid to recyclebin item created event. It enables to delete remote Rocket.Chat groups';
$string['validationgroupnameregex'] = 'Rocket.Chat group validation name regular expression to remove invalid characters';
$string['validationgroupnameregex_desc'] = 'Moodle will replace every unauthorized caracters by _. This regexp is the exact negation of the Rocket.Chat server one concerning group name validation';
$string['embedded_display_mode'] = 'Rocket.Chat embedded display mode';
$string['embedded_display_mode_desc'] = 'If checked, will remove left panel on Rocket.Chat web interface (hidding other channels)';
$string['embedded_display_mode_setting'] = 'Rocket.Chat embedded display mode';
$string['embedded_display_mode_setting_desc'] = 'If checked, will remove left panel on Rocket.Chat web interface (hidding other channels)';
$string['rocketchat:addinstance'] = 'Add a Rocket.Chat instance';
$string['rocketchat:candefineroles'] = 'Override role mapping through module instance definition';
$string['modulenameplural'] = 'Rocket.Chat instances';
$string['removemessages'] = 'Remove all messages';
$string['removeditem'] = 'removed messages on {$a->rocketchatid}';
$string['datastransmittedtorc'] = 'datas transmitted to Rocketchat';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:username'] = 'User username';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:firstname'] = 'User firstname';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:firstname'] = 'User lastname';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:email'] = 'User email';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:rocketchatids'] = 'Rocket.Chat remote private groups ids where user is enrolled in';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server'] = 'Datas transmitted to remote Rocket.Chat servers';
$string['connection-success'] = 'Connection succesfully establish';
$string['testconnection'] = 'Test connection to Rocket.Chat';
$string['testtitle'] = 'Rocket.Chat connection test.';
$string['testconnectionfailure'] = 'Connection test require following settings :</br>'
    .'instanceurl, restapiroot, apiuser and apipassword.</br>Please check that theses are all filled in.';
$string['settings'] = 'Rocket.Chat plugin settings';
$string['errorintestwhilegconnection'] = 'Error while testing connection';
$string['connectiontestresult'] = 'Connection test result';
$string['groupecreationerror'] = "Error while creating Rocket.Chat remote group";
$string['testerrormessage'] = 'Error message :</br>{$a}';
$string['testerrorcode'] = 'Error code : {$a}';
$string['rcgrouperror'] = 'Remote Rocket.Chat group can\'t be retrieved. Please contact your system administrator. Error code {$a}.';
$string['usernamehook'] = 'Activate username hook.';
$string['usernamehook_desc'] =
    'By activating this option it will be possible to chang moodle username to fit Rocket.Chat username.'
    .'</br>Create a hooklib.php file in the module rocketchat installation directory.'
    .'</br>code a moodle_username_to_rocketchat function that will return the transformed moodle username to fit Rocket.Chat one.'
    .'</br>hooklib-example.php is given as example.';
$string['background_enrolment_task'] = 'Pass user enrolment/unenrolments in remote Rocket.Chat as background tasks.';
$string['background_enrolment_task_desc'] = 'These to solve performance issue while enrolling large amounts of users.'
    .'</br>This will prevent the enroller to wait on course enrolment page while enrolling/unenrolling large amount of users.'
    .'</br>Choice of enrol cohort and flatfile, if enabled, are strongly advised';
$string['background_add_instance'] = 'Pass Rocket.Chat enrolments to background task while creating a new module instance';
$string['background_add_instance_desc'] = 'This will prevent to wait on the module creation page';
$string['background_restore'] = 'Pass Rocket.Chat enrolments to background task while dupplicating a rocket.chat modules';
$string['background_restore_desc'] = 'This will prevent to wait while dupplicating a Rocket.Chat module';
$string['background_synchronize'] = 'Pass Rocket.Chat enrolments to background task while synchronizing enrollees.';
$string['background_synchronize_desc'] = 'This occurs after a course or a Rocket.Chat module is restored from recyclebin,  ';