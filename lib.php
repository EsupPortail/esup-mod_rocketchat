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
 * Library of interface functions and constants.
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/mod/rocketchat/locallib.php');
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function rocketchat_supports($feature) {
    if (!$feature) {
        return null;
    }
    // TODO check each feature groups and backup
    $features = array(
        (string) FEATURE_IDNUMBER => true,
        (string) FEATURE_GROUPS => true,
        (string) FEATURE_GROUPINGS => true,
        (string) FEATURE_MOD_INTRO => true,
        (string) FEATURE_BACKUP_MOODLE2 => false, //TODO
        (string) FEATURE_COMPLETION_TRACKS_VIEWS => true,
        (string) FEATURE_GRADE_HAS_GRADE => false,
        (string) FEATURE_GRADE_OUTCOMES => false,
        (string) FEATURE_SHOW_DESCRIPTION => true,
    );
    if (isset($features[(string) $feature])) {
        return $features[$feature];
    }
    return null;
}

/**
 * Saves a new instance of the mod_rocketchat into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_rocketchat_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function rocketchat_add_instance($moduleinstance, $mform = null) {
    global $DB, $CFG;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = $moduleinstance->timecreated;
    $cmid       = $moduleinstance->coursemodule;
    $course = $DB->get_record('course', array('id' => $moduleinstance->course));
    $groupname = mod_rocketchat_tools::rocketchat_group_name($cmid, $course);
    $rocketchatapimanager = new rocket_chat_api_manager();
    $moduleinstance->rocketchatid = $rocketchatapimanager->create_rocketchat_group($groupname);
    $moduleinstance->rocketchatname = $groupname;
    if(is_null($moduleinstance->rocketchatid)){
        print_error('an error occured while creating Rocket.Chat group');
    }
    $id = $DB->insert_record('rocketchat', $moduleinstance);
    // TODO update calendar here when calendar considerations will be implemented
    return $id;
}

/**
 * Updates an instance of the mod_rocketchat in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_rocketchat_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function rocketchat_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('rocketchat', $moduleinstance);
}

/**
 * Removes an instance of the mod_rocketchat from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function rocketchat_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('rocketchat', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('rocketchat', array('id' => $id));

    return true;
}
