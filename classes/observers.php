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
 * @category    observer
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rocketchat;

use mod_rocketchat\api\manager\rocket_chat_api_manager;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');

class observers {

    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;
        if(\mod_rocketchat_tools::rocketchat_enabled()) {
            $context = $event->get_context();
            $userid = $event->relateduserid;
            $moodleuser = $DB->get_record('user', array('id' => $userid));
            $roleid = $event->objectid;
            $rocketchatapimanager = new rocket_chat_api_manager();
            if ($context->contextlevel == CONTEXT_COURSE && is_enrolled($context, $userid)) {
                $courseid = $context->instanceid;
                //search for rocketchat module instances concerned
                $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
                foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                    $ismoderator = false;
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->moderatorroles))) {
                        $return =
                            $rocketchatapimanager->enrol_moderator_to_group($rocketchatmoduleinstance->rocketchatid,
                                $rocketchatmoduleinstance->rocketchatname,
                                $moodleuser);
                        $ismoderator = true;
                    }
                    if (!$ismoderator) {
                        if (in_array($roleid, explode(',', $rocketchatmoduleinstance->userroles))) {
                            $rocketchatapimanager->enrol_user_to_group($rocketchatmoduleinstance->rocketchatid,
                                $rocketchatmoduleinstance->rocketchatname, $moodleuser);
                        }
                    }

                }
            }
        }
    }

    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;
        if(\mod_rocketchat_tools::rocketchat_enabled()) {
            $context = $event->get_context();
            $userid = $event->relateduserid;
            $moodleuser = $DB->get_record('user', array('id' => $userid));
            $roleid = $event->objectid;
            $courseid = $context->instanceid;
            $identifier = new \stdClass();
            $identifier->username = $moodleuser->username;
            if ($context->contextlevel == CONTEXT_COURSE) {
                $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
                foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                    $rocketchatapimanager = new rocket_chat_api_manager();
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->moderatorroles))) {
                        $rocketchatapimanager->unenrol_moderator_from_group($rocketchatmoduleinstance->rocketchatid,
                            $rocketchatmoduleinstance->rocketchatname, $moodleuser);
                    }
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->userroles))) {
                        $rocketchatapimanager->unenrol_user_from_group($rocketchatmoduleinstance->rocketchatid,
                            $rocketchatmoduleinstance->rocketchatname, $moodleuser);
                    }
                }
            }
        }
    }

    public static function course_bin_item_created(\tool_recyclebin\event\course_bin_item_created $event){
        global $DB;
        if(\mod_rocketchat_tools::rocketchat_enabled()) {
            $cminfos = $event->other;
            // Check that this is a Rocket.Chat module instance
            $rocketchatmodule = $DB->get_record_sql('select * from {course_modules} cm inner join {modules} m on m.id=cm.module where cm.id=:cmid',
                array('cmid' => $cminfos['cmid']));
            if($rocketchatmodule) {
                $rocketchat = $DB->get_record('rocketchat', array('id' => $cminfos['instanceid']));
                //insert intem into association table
                $record = new \stdClass();
                $record->cmid = $cminfos['cmid'];
                $record->rocketchatid = $rocketchat->rocketchatid;
                $record->binid = $event->objectid;
                $DB->insert_record('rocketchatxrecyclebin', $record);
            }
        }
    }

    public static function course_bin_item_deleted(\tool_recyclebin\event\course_bin_item_deleted $event){
        global $DB;
        if(\mod_rocketchat_tools::rocketchat_enabled()) {
            $rocketchatrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('binid' => $event->objectid));
            if ($rocketchatrecyclebin) {
                $rocketchatapimanager = new rocket_chat_api_manager();
                $rocketchatapimanager->delete_rocketchat_group($rocketchatrecyclebin->rocketchatid);
                $DB->delete_records('rocketchatxrecyclebin', array('id' => $rocketchatrecyclebin->id));
            }
        }
    }

    public static function course_bin_item_restored(\tool_recyclebin\event\course_bin_item_restored $event) {
        global $DB;
        if(\mod_rocketchat_tools::rocketchat_enabled()) {
            $rocketchatrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('binid' => $event->objectid));
            $rocketchatapimanager = new rocket_chat_api_manager();
            $group = $rocketchatapimanager->get_rocketchat_group_object($rocketchatrecyclebin->rocketchatid);
            $group->unarchive();
            $DB->delete_records('rocketchatxrecyclebin', array('id' => $rocketchatrecyclebin->id));
        }
    }
}