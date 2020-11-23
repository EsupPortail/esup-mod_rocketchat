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
        if (\mod_rocketchat_tools::rocketchat_enabled()) {
            $context = $event->get_context();
            $userid = $event->relateduserid;
            $moodleuser = $DB->get_record('user', array('id' => $userid));
            $roleid = $event->objectid;
            $rocketchatapimanager = null;
            if ($context->contextlevel == CONTEXT_COURSE && is_enrolled($context, $userid)) {
                $courseid = $context->instanceid;
                // Search for rocketchat module instances concerned.
                $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
                if(!empty($rocketchatmoduleinstances)){
                    $rocketchatapimanager = new rocket_chat_api_manager();
                }
                foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                    $ismoderator = false;
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->moderatorroles))) {
                        $return =
                            $rocketchatapimanager->enrol_moderator_to_group($rocketchatmoduleinstance->rocketchatid,
                                $moodleuser);
                        $ismoderator = true;
                    }
                    if (!$ismoderator) {
                        if (in_array($roleid, explode(',', $rocketchatmoduleinstance->userroles))) {
                            $rocketchatapimanager->enrol_user_to_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                        }
                    }

                }
            }
        }
    }

    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled()) {
            $context = $event->get_context();
            $userid = $event->relateduserid;
            $moodleuser = $DB->get_record('user', array('id' => $userid));
            $roleid = $event->objectid;
            $courseid = $context->instanceid;
            $identifier = new \stdClass();
            $identifier->username = $moodleuser->username;
            $rocketchatapimanager = null;
            if ($context->contextlevel == CONTEXT_COURSE) {
                $rocketchatmoduleinstances = \mod_rocketchat_tools::get_rocketchat_module_instances($courseid);
                if(!empty($rocketchatmoduleinstances)){
                    $rocketchatapimanager = new rocket_chat_api_manager();
                }
                foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->moderatorroles))) {
                        $rocketchatapimanager->unenrol_moderator_from_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                    }
                    if (in_array($roleid, explode(',', $rocketchatmoduleinstance->userroles))) {
                        $rocketchatapimanager->unenrol_user_from_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                    }
                }
            }
        }
    }

    public static function course_bin_item_created(\tool_recyclebin\event\course_bin_item_created $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
            $cminfos = $event->other;
            // Check that this is a Rocket.Chat module instance.
            $sql = 'select * from {course_modules} cm inner join {modules} m on m.id=cm.module where cm.id=:cmid';
            $rocketchatmodule = $DB->get_record_sql($sql, array('cmid' => $cminfos['cmid']));
            if ($rocketchatmodule) {
                $rocketchat = $DB->get_record('rocketchat', array('id' => $cminfos['instanceid']));
                // Insert item into association table.
                $record = new \stdClass();
                $record->cmid = $cminfos['cmid'];
                $record->rocketchatid = $rocketchat->rocketchatid;
                $record->binid = $event->objectid;
                $DB->insert_record('rocketchatxrecyclebin', $record);
            }
        }
    }

    public static function course_bin_item_deleted(\tool_recyclebin\event\course_bin_item_deleted $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
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
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
            $rocketchatrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('binid' => $event->objectid));
            $rocketchatapimanager = new rocket_chat_api_manager();
            $rocketchatapimanager->unarchive_rocketchat_group($rocketchatrecyclebin->rocketchatid);
            $DB->delete_records('rocketchatxrecyclebin', array('id' => $rocketchatrecyclebin->id));
            // Synchronise members.
            \mod_rocketchat_tools::synchronize_group_members($rocketchatrecyclebin->rocketchatid);
        }
    }

    public static function category_bin_item_created(\tool_recyclebin\event\category_bin_item_created $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
            $courseinfos = $event->other;
            // Check that this is a Rocket.Chat module instance.
            $sql = 'select cm.id, cm.instance from {course_modules} cm inner join {modules} m on m.id=cm.module '
                .'where cm.course=:courseid and m.name=:modname';
            $rocketchatmodules = $DB->get_records_sql($sql,
                array('courseid' => $courseinfos['courseid'], 'modname' => 'rocketchat'));
            foreach ($rocketchatmodules as $rocketchatmodule) {
                if ($rocketchatmodule) {
                    $rocketchat = $DB->get_record('rocketchat', array('id' => $rocketchatmodule->instance));
                    // Insert item into association table.
                    $record = new \stdClass();
                    $record->cmid = $rocketchatmodule->id;
                    $record->rocketchatid = $rocketchat->rocketchatid;
                    $record->binid = $event->objectid;
                    $DB->insert_record('rocketchatxrecyclebin', $record);
                }
            }
        }
    }

    public static function category_bin_item_deleted(\tool_recyclebin\event\category_bin_item_deleted $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
            $rocketchatrecyclebins = $DB->get_records('rocketchatxrecyclebin', array('binid' => $event->objectid));
            $rocketchatapimanager = null;
            if(!empty($rocketchatrecyclebins)){
                $rocketchatapimanager = new rocket_chat_api_manager();
            }
            foreach ($rocketchatrecyclebins as $rocketchatrecyclebin) {
                if ($rocketchatrecyclebin) {
                    $rocketchatapimanager->delete_rocketchat_group($rocketchatrecyclebin->rocketchatid);
                    $DB->delete_records('rocketchatxrecyclebin', array('id' => $rocketchatrecyclebin->id,
                        'rocketchatid' => $rocketchatrecyclebin->rocketchatid));
                }
            }
        }
    }

    public static function category_bin_item_restored(\tool_recyclebin\event\category_bin_item_restored $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && \mod_rocketchat_tools::is_patch_installed()) {
            $rocketchatrecyclebins = $DB->get_records('rocketchatxrecyclebin', array('binid' => $event->objectid));
            $rocketchatapimanager = null;
            if(!empty($rocketchatrecyclebins)){
                $rocketchatapimanager = new rocket_chat_api_manager();
            }
            foreach ($rocketchatrecyclebins as $rocketchatrecyclebin) {
                $rocketchatapimanager->unarchive_rocketchat_group($rocketchatrecyclebin->rocketchatid);
                $DB->delete_records('rocketchatxrecyclebin', array('id' => $rocketchatrecyclebin->id,
                    'rocketchatid' => $rocketchatrecyclebin->rocketchatid));
            }
        }
    }

    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $DB;
        if (\mod_rocketchat_tools::rocketchat_enabled() && $event->other['modulename'] == 'rocketchat') {
            $coursemodule = $DB->get_record('course_modules', array('id' => $event->objectid));
            $rocketchat = $DB->get_record('rocketchat', array('id' => $event->other['instanceid']));
            if ($rocketchat) {
                $rocketchatapimanager = new rocket_chat_api_manager();
                if (!$coursemodule->visible or !$coursemodule->visibleoncoursepage) {
                    // Can't detect visibility changind here.
                    $rocketchatapimanager->archive_rocketchat_group($rocketchat->rocketchatid);
                } else if ($coursemodule->visible && $coursemodule->visibleoncoursepage) {
                    $rocketchatapimanager->unarchive_rocketchat_group($rocketchat->rocketchatid);
                }
            }
        }
    }
}