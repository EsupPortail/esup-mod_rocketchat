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
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/mod/rocketchat/locallib.php');
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing theadd feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function rocketchat_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
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
    global $DB, $USER;
    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = $moduleinstance->timecreated;
    $cmid       = $moduleinstance->coursemodule;
    $course = $DB->get_record('course', array('id' => $moduleinstance->course));
    $groupname = mod_rocketchat_tools::rocketchat_group_name($cmid, $course);
    $rocketchatapimanager = new rocket_chat_api_manager();
    $moduleinstance->rocketchatid = $rocketchatapimanager->create_rocketchat_group($groupname);
    if (is_null($moduleinstance->rocketchatid)) {
        throw new moodle_exception('an error occured while creating Rocket.Chat group');
    }
    if ((boolean)get_config('mod_rocketchat', 'retentionfeature')) {
        $retentionsettings = array(
            'retentionenabled' =>
                property_exists($moduleinstance, 'retentionenabled') ? $moduleinstance->retentionenabled : false,
            'maxage' => $moduleinstance->maxage,
            'filesonly' => property_exists($moduleinstance, 'filesonly') ? $moduleinstance->filesonly : false,
            'excludepinned' => property_exists($moduleinstance, 'excludepinned') ? $moduleinstance->excludepinned : false
        );
        $rocketchatapimanager->save_rocketchat_group_settings($moduleinstance->rocketchatid, $retentionsettings);
    }
    if (!$moduleinstance->visible || !$moduleinstance->visibleoncoursepage) {
        $group = $rocketchatapimanager->get_rocketchat_group_object($moduleinstance->rocketchatid);
        $group->archive();
    }
    $id = $DB->insert_record('rocketchat', $moduleinstance);
    // Force creator if current user has a role for this instance.
    $moderatorrolesids = array_filter(explode(',', $moduleinstance->moderatorroles));
    $userrolesids = array_filter(explode(',', $moduleinstance->userroles));
    $forcecreator = mod_rocketchat_tools::has_rocket_chat_user_role($userrolesids, $USER, context_course::instance($course->id))
        || mod_rocketchat_tools::has_rocket_chat_moderator_role($moderatorrolesids, $USER, context_course::instance($course->id));
    mod_rocketchat_tools::enrol_all_concerned_users_to_rocketchat_group(
        $moduleinstance,
        get_config('mod_rocketchat', 'background_add_instance'),
        $forcecreator);
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
    $moduleinstance->id = property_exists($moduleinstance, 'id') ? $moduleinstance->id : $moduleinstance->instance;
    $moduleinstance->retentionenabled =
        property_exists($moduleinstance, 'retentionenabled') ? $moduleinstance->retentionenabled : false;
    $moduleinstance->filesonly =
        property_exists($moduleinstance, 'filesonly') ? $moduleinstance->filesonly : false;
    $moduleinstance->excludepinned =
        property_exists($moduleinstance, 'excludepinned') ? $moduleinstance->excludepinned : false;
    $rocketchat = $DB->get_record('rocketchat', array('id' => $moduleinstance->id));
    $return = $DB->update_record('rocketchat', $moduleinstance);
    if ($return) {
        $rocketchatapimanager = new rocket_chat_api_manager();
        if ((boolean)get_config('mod_rocketchat', 'retentionfeature')) {
            $retentionsettings = array(
                'retentionenabled' => $moduleinstance->retentionenabled,
                'maxage' => $moduleinstance->maxage,
                'filesonly' => $moduleinstance->filesonly,
                'excludepinned' => $moduleinstance->excludepinned

            );
            $rocketchatapimanager->save_rocketchat_group_settings($rocketchat->rocketchatid, $retentionsettings);
        }
        \mod_rocketchat_tools::synchronize_group_members($rocketchat->rocketchatid,
            get_config('mod_rocketchat', 'background_synchronize'));
    }
    return $return;
}

/**
 * Removes an instance of the mod_rocketchat from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function rocketchat_delete_instance($id) {
    global $DB;
    $rocketchat = $DB->get_record('rocketchat', array('id' => $id));
    if (!$rocketchat) {
        return false;
    }
    // Treat remote Rocket.Chat remote private group depending of.
    $rocketchatapimanager = new rocket_chat_api_manager();
    list(, $caller) = debug_backtrace(false);
    if ((\tool_recyclebin\course_bin::is_enabled() && $caller['function'] == 'course_delete_module')
        || (\tool_recyclebin\category_bin::is_enabled() && $caller['function'] == 'remove_course_contents')) {
        $rocketchatapimanager->archive_rocketchat_group($rocketchat->rocketchatid);
    } else {
        $rocketchatapimanager->delete_rocketchat_group($rocketchat->rocketchatid);
    }
    $DB->delete_records('rocketchat', array('id' => $id));

    return true;
}

/**
 * Course reset form definition
 * @param $mform
 * @throws coding_exception
 */
function rocketchat_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'rocketchatheader', get_string('modulenameplural', 'mod_rocketchat'));
    $mform->addElement('advcheckbox', 'reset_rocketchat', get_string('removemessages', 'mod_rocketchat'));
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function rocketchat_reset_course_form_defaults($course) {
    return array('reset_rocketchat' => 1);
}

/**Remove all messages
 * @param $data
 */
function rocketchat_reset_userdata($data) {
    global $DB;
    $status = [];
    // Delete remote Rocket.Chat messages.
    if (!empty($data->reset_rocketchat)) {
        $sql = 'select cm.id, r.id as instanceid, r.rocketchatid from {course_modules} cm inner join {modules} m on m.id=cm.module '
            .'inner join {rocketchat} r on r.id=cm.instance'
            .' where cm.course=:courseid and m.name=:modname';
        $rocketchats = $DB->get_records_sql($sql,
            array('courseid' => $data->courseid, 'modname' => 'rocketchat'));
        if ($rocketchats) {
            $rocketchatapimanager = new rocket_chat_api_manager();
            foreach ($rocketchats as $rocketchat) {
                $rocketchatapimanager->clean_history($rocketchat->rocketchatid);
                $status[] = array('component' => get_string('modulenameplural', 'rocketchat')
                , 'item' => get_string("removeditem", 'mod_rocketchat', $rocketchat)
                , 'error' => false);
            }
        }

    }
    return $status;

}

/**
 * Add a get_coursemodule_info function in case rocketchat instance wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function rocketchat_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat';
    if (!$rocketchat = $DB->get_record('rocketchat', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $rocketchat->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('rocketchat', $rocketchat, $coursemodule->id, false);
    }
    // Not populate some other values.
    $result->customdata = null;
    return $result;
}
