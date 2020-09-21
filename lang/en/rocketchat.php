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
$string['name'] = 'instance name (in moodle)';
$string['rocketchatname'] = 'Group name (in Rocket.Chat)';
$string['instanceurl'] = 'Rocket.Chat instance url';
$string['instanceurl_desc'] = 'Rocket.Chat instance url (ex: https://rocketchat.univ.fr)';
$string['restapiroot'] = 'Rocket.Chat rest api root';
$string['restapiroot_desc'] = 'Rocket.Chat rest api root';
$string['apiuser'] = 'Rocketchat api user';
$string['apiuser_desc'] = 'Rocketchat api user';
$string['apipassword'] = 'Rocketchat api password';
$string['apipassword_desc'] = 'Rocketchat api password';
$string['norocketchats'] = 'No Rocket.Chat module instances.';
$string['groupnametoformat'] = 'Formatted group name.';
$string['groupnametoformat_desc'] = 'Formatted group name. String format %arg is possible with the following parameters moodleid, moodleshortname, moodlefullname, moduleid, modulemoodleid (unique whitin all your possible moodle),  courseid, courseshortname, coursefullname';
$string['joinrocketchat'] = 'Join rocket chat session';
$string['displaytype'] = 'Display type';
$string['displaynew'] = 'Display in new window';
$string['displaypopup'] = 'Display in popup window';
$string['displaycurrent'] = 'Display in current window';
$string['popupheight'] = 'Popup height';
$string['popupwidth'] = 'Popup width';
$string['pluginadministration'] = 'Rocket.Chat administration';
$string['deletionmode'] = 'Rocket.Chat remote private group deletion mode';
$string['deletionmode_desc'] = 'Rocket.Chat remote private group deletion mode. Deletion hard  : hard delete Rocket.Chat, archive : archive remote Rocket.Chat private group';
$string['deletion_archive'] = 'Archive remote Rocket.Chat group';
$string['deletion_hard'] = 'Hard delete remote Rocket.Chat group';
$string['defaultmoderatorroles'] = 'Rocket.Chat moderators.';
$string['moderatorroles'] = 'Moodle roles in course that will be Rocket.Chat moderators';
$string['defaultmoderatorroles_desc'] = 'Moodle roles in course that will be Rocket.Chat moderators.';
$string['defaultuserroles'] = 'Rocket.Chat users.';
$string['userroles'] = 'Moodle roles in course that will be Rocket.Chat users (with normal user rights)';
$string['defaultuserroles_desc'] = 'Moodle roles in course that will be Rocket.Chat users (with normal user rights).';
$string['mod_rocketchat:addinstance'] = 'Ajouter une instance de module Rocket.Chat';
$string['mod_rocketchat:view'] = 'Voir les instances du module Rocket.Chat';
$string['mod_rocketchat:candefineroles'] = 'Peut définir les roles à propager pour les inscirptions aux groupes privés Rocket.Chat';
$string['rocketchat_nickname'] = '{$a->firstname} {$a->lastname}';
$string['create_user_account_if_not_exists'] = 'while enrolling user, create Rocket.Chat corresponding user account(username) if not exists.';
$string['create_user_account_if_not_exists_desc'] = 'while enrolling user, create Rocket.Chat corresponding user account(username) if not exists.';

