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
 * All the steps to restore mod_rocketchat are defined here.
 *
 * @package     mod_rocketchat
 * @category    restore
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

// For more information about the backup and restore process, please visit:
// https://docs.moodle.org/dev/Backup_2.0_for_developers
// https://docs.moodle.org/dev/Restore_2.0_for_developers

/**
 * Defines the structure step to restore one mod_rocketchat activity.
 */
class restore_rocketchat_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {
        $paths = array();

        $paths[] = new restore_path_element('rocketchat', '/activity/rocketchat');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the rocketchat restore data.
     *
     * @param array $data Parsed element data.
     */
    protected function process_rocketchat($data) {
        global $DB;
        $restorewithrocketchatid = $this->task->get_setting_value('restorewithrocketchatid');
        $data = (object)$data;
        $oldid = $data->id;
        $modulename = $this->task->get_modulename();


        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }
        $newitemid = $DB->insert_record('rocketchat', $data);
        $this->apply_activity_instance($newitemid);

    }

    /**
     * Defines post-execution actions.
     */
    protected function after_execute() {
        global $DB;
        // Add rocketchat related files
        $this->add_related_files('mod_rocketchat', 'intro', null);
        $cmid = $this->get_task()->get_moduleid();
        $instanceid = $this->get_task()->get_activityid();
        $restorewithrocketchatid = $this->task->get_setting_value('restorewithrocketchatid');
        if(!$restorewithrocketchatid){
            $course = $DB->get_record('course', array('id' => $this->get_courseid()));
            $groupname = mod_rocketchat_tools::rocketchat_group_name($cmid, $course);
            $rocketchatapimanager = new rocket_chat_api_manager();
            $rocketchatid = $rocketchatapimanager->create_rocketchat_group($groupname);
            // Update rocketchat table
            $rocketchat = $DB->get_record('rocketchat', array('id' => $instanceid));
            $rocketchat->rocketchatid = $rocketchatid;
            $rocketchat->rocketchatname = $groupname;
            $DB->update_record('rocketchat', $rocketchat);

        }


        return;
    }
}
