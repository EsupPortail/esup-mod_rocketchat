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
 * observers file
 * @package     mod_rocketchat
 * @category    event
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Céline Pervès <cperves@unistra.fr>
 */

namespace mod_rocketchat;

use mod_rocketchat\api\manager\rocket_chat_api_manager;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');

class observers {

    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;
        $context = $event->get_context();
        $userid = $event->relateduserid;
        $moodleuser = $DB->get_record('user', array('id' => $userid));
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        $roleid = $event->objectid;
        if($context->contextlevel == CONTEXT_COURSE && is_enrolled($context, $userid)) {
            $courseid = $context->instanceid;
            //search for rocketchat module instances concerned
            $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
            foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                $ismoderator = false;
                if(in_array($roleid, explode(',',$rocketchatmoduleinstance->moderatorroles))) {
                    $rocketchatapimanager = new rocket_chat_api_manager();
                    $group = $rocketchatapimanager->get_rocketchat_group_object($rocketchatmoduleinstance->rocketchatid,
                        $rocketchatmoduleinstance->rocketchatname);
                    $user = $group->user_info($identifier);
                    $return = $group->invite($user->_id);
                    if($return){
                        $return =
                            $group->addModerator($user->_id); //TODO take in charge user for multiplatform purpose
                    }
                    if (!$return) {
                        debugging("User $moodleuser->username not added as moderator to remote Rocket.Chat group",
                            DEBUG_MINIMAL);
                    }
                    $ismoderator = true;
                }
                if (!$ismoderator) {
                    if(in_array($roleid, explode(',',$rocketchatmoduleinstance->userroles))){
                        $rocketchatapimanager = new rocket_chat_api_manager();
                        $group = $rocketchatapimanager->get_rocketchat_group_object($rocketchatmoduleinstance->rocketchatid,
                            $rocketchatmoduleinstance->rocketchatname);
                        $user = $group->user_info($identifier);
                        $return = $group->invite($user->_id);
                        if (!$return) {
                            debugging("User $moodleuser->username not added as user to remote Rocket.Chat group",
                                DEBUG_MINIMAL);
                        }
                    }
                }

            }
        }
    }

    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;
        $context = $event->get_context();
        $userid = $event->relateduserid;
        $moodleuser = $DB->get_record('user', array('id' => $userid));
        $roleid = $event->objectid;
        $courseid = $context->instanceid;
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        if($context->contextlevel == CONTEXT_COURSE) {
            $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
            foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                $rocketchatapimanager = new rocket_chat_api_manager();
                if(in_array($roleid, explode(',',$rocketchatmoduleinstance->moderatorroles))) {
                    $group = $rocketchatapimanager->get_rocketchat_group_object($rocketchatmoduleinstance->rocketchatid,
                        $rocketchatmoduleinstance->rocketchatname);
                    $user = $group->user_info($identifier);
                    $return = $group->removeModerator($user->_id); //TODO take in charge user for multiplatform purpose
                    if($return){
                        $return = $group->kick($user->_id);
                    }
                    if (!$return) {
                        debugging("User $user->username not removed as moderator to remote Rocket.Chat group",
                            DEBUG_MINIMAL);
                    }
                }
                if(in_array($roleid, explode(',',$rocketchatmoduleinstance->userroles))) {
                    $group = $rocketchatapimanager->get_rocketchat_group_object($rocketchatmoduleinstance->rocketchatid,
                        $rocketchatmoduleinstance->rocketchatname);
                    $user = $group->user_info($identifier);
                    $return = $group->kick($user->_id); //TODO take in charge user for multiplatform purpose
                    if (!$return) {
                        debugging("User $user->username not kicked as user to remote Rocket.Chat group",
                            DEBUG_MINIMAL);
                    }
                }
            }
        }
    }

    public static function user_enrolment_updated(\core\event\course_deleted $event) {

    }

    public static function user_deleted(\core\event\course_deleted $event) {

    }

}