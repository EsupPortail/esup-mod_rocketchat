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
 * Plugin internal classes, functions and constants are defined here.
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_rocketchat\api\manager\rocket_chat_api_manager;
use RocketChat\RocketChatException;

class mod_rocketchat_tools {
    /** Display new window */
    const DISPLAY_NEW = 1;
    /** Display in curent window */
    const DISPLAY_CURRENT = 2;
    /** Display in popup */
    const DISPLAY_POPUP = 3;

    /**
     * Construct display options.
     * @return array
     * @throws coding_exception
     */
    public static function get_display_options() {
        $options = array();
        $options[self::DISPLAY_NEW] = get_string('displaynew', 'mod_rocketchat');
        $options[self::DISPLAY_CURRENT] = get_string('displaycurrent', 'mod_rocketchat');
        $options[self::DISPLAY_POPUP] = get_string('displaypopup', 'mod_rocketchat');
        return $options;
    }

    public static function get_roles_options() {
        global $DB;
        return $DB->get_records('role', array(), 'shortname asc');
    }

    public static function rocketchat_group_name($cmid, $course) {
        global $CFG, $SITE;
        $formatarguments = new stdClass();
        $formatarguments->moodleshortname = $SITE->shortname;
        $formatarguments->moodlefullnamename = $SITE->fullname;
        $formatarguments->moodleid = sha1($CFG->wwwroot);
        $formatarguments->moduleid = $cmid;
        $formatarguments->modulemoodleid = sha1($SITE->shortname.'_'.$cmid);
        $formatarguments->courseid = $course->id;
        $formatarguments->courseshortname = $course->shortname;
        $formatarguments->coursefullname = $course->fullname;
        $groupnameformat = get_config('mod_rocketchat', 'groupnametoformat');
        $groupnameformat = is_null($groupnameformat) ? '{$a->moodleid}_{$a->courseshortname}_{$a->moduleid}' : $groupnameformat;
        $groupname = self::format_string($groupnameformat, $formatarguments);
        return self::sanitize_groupname($groupname);
    }



    public static function format_string($string, $a) {
        if ($a !== null) {
            // Process array's and objects (except lang_strings).
            if (is_array($a) or (is_object($a) && !($a instanceof lang_string))) {
                $a = (array)$a;
                $search = array();
                $replace = array();
                foreach ($a as $key => $value) {
                    if (is_int($key)) {
                        // We do not support numeric keys - sorry!
                        continue;
                    }
                    if (is_array($value) or (is_object($value) && !($value instanceof lang_string))) {
                        // We support just string or lang_string as value.
                        continue;
                    }
                    $search[]  = '{$a->'.$key.'}';
                    $replace[] = (string)$value;
                }
                if ($search) {
                    $string = str_replace($search, $replace, $string);
                }
            } else {
                $string = str_replace('{$a}', (string)$a, $string);
            }
        }
        return $string;
    }

    public static function get_rocketchat_module_instances($courseid) {
        global $DB;
        $sql = 'select cm.*, r.rocketchatid, r.moderatorroles, r.userroles'
            .' from {course_modules} cm inner join {modules} m on m.id=cm.module inner join {rocketchat} r on r.id=cm.instance '
            .'where m.name=:rocketchat and cm.course=:courseid';
        $moduleinstances = $DB->get_records_sql($sql , array('courseid' => $courseid, 'rocketchat' => 'rocketchat'));
        return $moduleinstances;
    }

    public static function get_rocketchat_module_instance($cmid) {
        global $DB;
        $sql = 'select cm.*, r.rocketchatid, r.moderatorroles, r.userroles'
            .' from {course_modules} cm inner join {modules} m on m.id=cm.module inner join {rocketchat} r on r.id=cm.instance '
            .'where m.name=:rocketchat and cm.id=:cmid';
        $moduleinstances = $DB->get_records_sql($sql , array('cmid' => $cmid, 'rocketchat' => 'rocketchat'));
        return $moduleinstances;
    }

    public static function has_rocketchat_module_instances($courseid) {
        global $DB;
        $sql = 'select cm.*, r.rocketchatid, r.moderatorroles, r.userroles'
            .' from {course_modules} cm inner join {modules} m on m.id=cm.module inner join {rocketchat} r on r.id=cm.instance '
            .'where m.name=:rocketchat and cm.course=:courseid';
        return $DB->record_exists_sql($sql , array('courseid' => $courseid, 'rocketchat' => 'rocketchat'));
    }

    public static function is_module_a_rocketchat_instance($cmid) {
        global $DB;
        $sql = 'select cm.*, r.rocketchatid, r.moderatorroles, r.userroles'
            .' from {course_modules} cm inner join {modules} m on m.id=cm.module inner join {rocketchat} r on r.id=cm.instance '
            .'where m.name=:rocketchat and cm.id=:cmid';
        return $DB->record_exists_sql($sql , array('cmid' => $cmid, 'rocketchat' => 'rocketchat'));
    }

    public static function enrol_all_concerned_users_to_rocketchat_group($rocketchatmoduleinstance,
        $background=false,
        $forcecreator=false
    ) {
        global $USER;
        $courseid = $rocketchatmoduleinstance->course;
        $coursecontext = context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext);
        foreach ($users as $user) {
            if (!$background || ($forcecreator && $user->id == $USER->id && !\core\session\manager::is_loggedinas())) {
                self::enrol_user_to_rocketchat_group($rocketchatmoduleinstance->rocketchatid,
                    $rocketchatmoduleinstance->moderatorroles,
                    $rocketchatmoduleinstance->userroles,
                    $user->id,
                    $coursecontext->id);
            } else {
                $taskenrolment = new \mod_rocketchat\task\enrol_user_to_rocketchat_group();
                $taskenrolment->set_custom_data(
                    array(
                        'rocketchatid' => $rocketchatmoduleinstance->rocketchatid,
                        'moderatorroles' => $rocketchatmoduleinstance->moderatorroles,
                        'userroles' => $rocketchatmoduleinstance->userroles,
                        'userid' => $user->id,
                        'coursecontextid' => $coursecontext->id
                    )
                );
                \core\task\manager::queue_adhoc_task($taskenrolment);
            }
        }
    }

    public static function synchronize_group_members($rocketchatmoduleinstance, $background = false) {
        global $DB;
        if (!is_object($rocketchatmoduleinstance)) {
            $rocketchatmoduleinstanceid = $rocketchatmoduleinstance;
            $rocketchatmoduleinstance = $DB->get_record('rocketchat', array('rocketchatid' => $rocketchatmoduleinstance));
            if (!$rocketchatmoduleinstance) {
                throw new moodle_exception("can't load rocketchat instance $rocketchatmoduleinstanceid in moodle");
            }
        }
        $courseid = $rocketchatmoduleinstance->course;
        $coursecontext = context_course::instance($courseid);
        $moodlemembers = get_enrolled_users($coursecontext);
        $rocketchatid = $rocketchatmoduleinstance->rocketchatid;
        $moderatorroles = $rocketchatmoduleinstance->moderatorroles;
        $moderatorroleids = array_filter(explode(',', $moderatorroles));
        $userroles = $rocketchatmoduleinstance->userroles;
        $userroleids = array_filter(explode(',', $userroles));
        if ($background) {
            $tasksynchronize = new \mod_rocketchat\task\synchronize_group();
            $tasksynchronize->set_custom_data(
                array(
                    'rocketchatid' => $rocketchatmoduleinstance->rocketchatid,
                    'moodlemembers' => $moodlemembers,
                    'moderatorrolesids' => $moderatorroleids,
                    'userrolesids' => $userroleids,
                    'coursecontextid' => $coursecontext->id
                )
            );
            \core\task\manager::queue_adhoc_task($tasksynchronize);
        } else {
            self::synchronize_group($rocketchatid,
                $moodlemembers, $moderatorroleids, $userroleids, $coursecontext);
        }
    }
    public static function synchronize_group_members_for_course($courseid) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $courseid));
        if (!$course) {
            throw new moodle_exception('course not found');
        }
        $rocketchatmodules = get_coursemodules_in_course('rocketchat', $courseid, 'rocketchatid');
        foreach ($rocketchatmodules as $rocketchatmodule) {
            self::synchronize_group_members($rocketchatmodule->rocketchatid);
        }
    }

    public static function synchronize_group_members_for_module($cmid) {
        global $DB;
        $rocketchat = $DB->get_record_sql(
            'select cm.*, r.rocketchatid from {course_modules} cm inner join {rocketchat} r on r.id=cm.instance'
            .' where cm.id=:cmid',
            array('cmid' => $cmid));
        if (!$rocketchat) {
            throw new moodle_exception('the given cmid is not corresponding with a rocket.chat module');
        }
        self::synchronize_group_members($rocketchat->rocketchatid);
    }

    public static function rocketchat_enabled() {

        global $DB;
        $module = $DB->get_record('modules', array('name' => 'rocketchat'));
        if (!empty($module->visible)) {
            $config = get_config('mod_rocketchat');
            if (!empty($config->instanceurl) && !empty($config->restapiroot)
                && !empty($config->apiuser) && !empty($config->apitoken)) {
                return true;
            }
        }
        return false;
    }

    public static function is_patch_installed() {
        return get_config('mod_rocketchat', 'recyclebin_patch');
    }

    public static function get_group_link($rocketchatid, $embbeded = 0) {
        try {
            $rocketchatmanager = new rocket_chat_api_manager();
            $groupname = $rocketchatmanager->get_groupname($rocketchatid);
            return $rocketchatmanager->get_instance_url() . '/group/' .$groupname.
                (empty($embbeded) ? '' : '?layout=embedded');
        } catch (\RocketChat\RocketChatException $re) {
            throw new moodle_exception(get_string('rcgrouperror', 'mod_rocketchat', $re->getCode()));
        }
    }

    /**
     * @param string $groupname
     * @return string|string[]|null
     * @throws dml_exception
     */
    public static function sanitize_groupname($groupname) {
        // Replace white spaces anyway.
        $groupname = preg_replace('/\/s/', '_', $groupname);
        // Proceed to replacements if any
        $replacementcouples = get_config('mod_rocketchat', 'replacementgroupnamecharacters');
        if ($replacementcouples) {
            $replacementcouples = preg_split ('/\R/', $replacementcouples);
            foreach ($replacementcouples as $replacementcouple){
                $replacementcouplearray = preg_split('/;/', $replacementcouple);
                if (count($replacementcouplearray) != 2) {
                    throw new moodle_exception(
                        'replacementgroupnamecharacters setting is not well formed. Please contact administrator.'
                    );
                    debugging('replacementgroupnamecharacters setting is not well formed. Please contact administrator.',
                        DEBUG_MINIMAL
                    );
                }
                $groupname = preg_replace('/'.$replacementcouplearray[0].'/', $replacementcouplearray[1], $groupname);
            }
        }
        $validationgroupnameregex = get_config('mod_rocketchat', 'validationgroupnameregex');
        if(!empty($validationgroupnameregex)) {
            $groupname =
                preg_replace($validationgroupnameregex, '_', $groupname);
        }
        if (empty($groupname)) {
            throw new moodle_exception('sanitized Rocket.Chat groupname can\'t be empty');
        }
        return $groupname;
    }

    public static function unenrol_user_everywhere($userid) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userid));
        $rocketchatapimanager = new rocket_chat_api_manager();
        if (!$user) {
            throw new moodle_exception("user $userid not found");
        }
        $courseenrolments = self::course_enrolments($userid);
        $rocketchatusername = self::rocketchat_username($user->username);
        if ($rocketchatapimanager->user_exists($rocketchatusername)) {
            $rocketchatuser = $rocketchatapimanager->get_user_infos($rocketchatusername);
            foreach ($courseenrolments as $courseenrolment) {
                $rocketchatapimanager->unenrol_user_from_group($courseenrolment->rocketchatid, $rocketchatuser);
            }
        }
    }
    public static function synchronize_user_enrolments($userid) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userid));
        $rocketchatapimanager = new rocket_chat_api_manager();

        if (!$user) {
            throw new moodle_exception("user $userid not found");
        }
        // Due to the fact that userroles is a string and role_assignments is an int,
        // No possibility to make a sql query without specific sql functions linked to database language.
        $courseenrolments = self::course_enrolments($userid);
        foreach ($courseenrolments as $courseenrolment) {
            $moderatorrolesids = array_filter(explode(',', $courseenrolment->moderatorroles));
            $userrolesids = array_filter(explode(',', $courseenrolment->userroles));
            $rocketchatmembers = $rocketchatapimanager->get_group_members($courseenrolment->rocketchatid);
            $rocketchatuser = self::synchronize_rocketchat_member($courseenrolment->rocketchatid,
                context_course::instance($courseenrolment->courseid),
                $user, $moderatorrolesids, $userrolesids, $rocketchatmembers);
            if (isset($rocketchatuser)
                && array_key_exists($rocketchatuser->username, $rocketchatmembers)
                && $rocketchatuser->username) {
                if ($rocketchatuser->username != get_config('mod_rocketchat', 'apiuser')
                    && $rocketchatuser->id != get_config('mod_rocketchat', 'apiuser')) {
                    $rocketchatapimanager->unenrol_user_from_group($courseenrolment->rocketchatid, $rocketchatuser);
                }
            }
        }
    }

    /**
     * @param array $userroleids
     * @param $moodlemember
     * @param context_course $coursecontext
     * @return array
     */
    public static function has_rocket_chat_user_role(array $userroleids, $moodlemember, context_course $coursecontext) {
        $isuser = false;
        foreach ($userroleids as $userroleid) {
            if (user_has_role_assignment($moodlemember->id, $userroleid, $coursecontext->id)) {
                $isuser = true;
                break;
            }
        }
        return $isuser;
    }

    public static function rocketchat_username($moodleusername) {
        global $CFG;
        $hook = get_config('mod_rocketchat', 'usernamehook');
        if ($hook) {
            require_once($CFG->dirroot.'/mod/rocketchat/hooklib.php');
            return moodle_username_to_rocketchat($moodleusername);
        }
        return $moodleusername;
    }

    /**
     * @param \context $context
     * @param int $userid
     * @param int $roleid
     * @param $moodleuser
     */
    public static function role_assign($courseid, int $roleid, $moodleuser, $context) {
        $rocketchatapimanager = array();
        $rocketchatmoduleinstances = null;
        if ($context->contextlevel == CONTEXT_COURSE) {
            $rocketchatmoduleinstances = self::get_rocketchat_module_instances($courseid);
        } else {
            $rocketchatmoduleinstances = self::get_rocketchat_module_instance($context->instanceid);
        }
        if (!empty($rocketchatmoduleinstances)) {
            $rocketchatapimanager = new rocket_chat_api_manager();
        }
        foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
            $ismoderator = false;
            if (in_array($roleid, array_filter(explode(',', $rocketchatmoduleinstance->moderatorroles)))) {
                $return =
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatmoduleinstance->rocketchatid,
                        $moodleuser);
                $ismoderator = true;
            }
            if (!$ismoderator) {
                if (in_array($roleid, array_filter(explode(',', $rocketchatmoduleinstance->userroles)))) {
                    $rocketchatapimanager->enrol_user_to_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                }
            }

        }

    }

    /**
     * @param \context $context
     * @param int $roleid
     * @param $moodleuser
     */
    public static function role_unassign($courseid, int $roleid, $moodleuser, $context) {
        $rocketchatmoduleinstances = array();
        if ($context->contextlevel == CONTEXT_COURSE) {
            $rocketchatmoduleinstances = self::get_rocketchat_module_instances($courseid);
        } else {
            $rocketchatmoduleinstances = self::get_rocketchat_module_instance($context->instanceid);
        }
        if (!empty($rocketchatmoduleinstances)) {
            $rocketchatapimanager = new rocket_chat_api_manager();
        }
        foreach ($rocketchatmoduleinstances as $rocketchatmoduleinstance) {
            $moderaorroles = explode(',', $rocketchatmoduleinstance->moderatorroles);
            $userroles = explode(',', $rocketchatmoduleinstance->userroles);
            $hasothermoderatorrole = false;
            $hasotheruserrole = false;
            $wasmoderator = false;
            // Has other moderator moodle roles?
            foreach ($moderaorroles as $moderatorrole) {
                if ($moderatorrole != $roleid) {
                    if (user_has_role_assignment($moodleuser->id, $moderatorrole, $context->id)) {
                        $hasothermoderatorrole = true;
                        break;
                    }
                }
            }
            // Has other user moodle roles?
            foreach ($userroles as $userrole) {
                if ($userrole != $roleid) {
                    if (user_has_role_assignment($moodleuser->id, $userrole, $context->id)) {
                        $hasotheruserrole = true;
                        break;
                    }
                }
            }
            if (in_array($roleid, array_filter($moderaorroles))) {
                $wasmoderator = true;
                if (!$hasothermoderatorrole) {
                    $rocketchatapimanager->revoke_moderator_in_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                }
            }
            $wasuser = false;
            if (!$hasothermoderatorrole) {
                if (in_array($roleid, array_filter($userroles))) {
                    $wasuser = true;
                    if (!$hasotheruserrole) {
                        $rocketchatapimanager->unenrol_user_from_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                    }
                } else if ($wasmoderator && !$hasotheruserrole) {
                    $rocketchatapimanager->unenrol_user_from_group($rocketchatmoduleinstance->rocketchatid, $moodleuser);
                }
            }
        }
    }

    /**
     * @param array $moderatorroleids
     * @param $moodlemember
     * @param context_course $coursecontext
     * @return array
     */
    public static function has_rocket_chat_moderator_role(array $moderatorroleids, $moodlemember,
        context_course $coursecontext) {
        $ismoderator = false;
        foreach ($moderatorroleids as $moderatorroleid) {
            if (user_has_role_assignment($moodlemember->id, $moderatorroleid, $coursecontext->id)) {
                $ismoderator = true;
                break;
            }
        }
        return $ismoderator;
    }

    /**
     * @param $rocketchatmoduleinstance
     * @param $user
     * @param context_course $coursecontext
     * @param rocket_chat_api_manager $rocketchatapimanager
     */
    public static function enrol_user_to_rocketchat_group($rocketchatid, $moderatorroles, $userroles, $userid, $coursecontextid) {
        global $DB;
        $rocketchatapimanager = new rocket_chat_api_manager();
        $user = $DB->get_record('user' , array('id' => $userid));
        if ($user) {
            $moderatorroleids = array_filter(explode(',', $moderatorroles));
            $ismoderator = false;
            foreach ($moderatorroleids as $moderatorroleid) {
                if (user_has_role_assignment($userid, $moderatorroleid, $coursecontextid)) {
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $user);
                }
            }
            if (!$ismoderator) {
                $userroleids = array_filter(explode(',', $userroles));
                foreach ($userroleids as $userroleid) {
                    if (user_has_role_assignment($userid, $userroleid, $coursecontextid)) {
                        $rocketchatapimanager->enrol_user_to_group($rocketchatid, $user);
                    }
                }
            }
        } else {
            debugging("enrol_user_to_rocketchat_group user $userid not exists");
        }
    }

    /**
     * @param $rocketchatid
     * @param array $moodlemembers
     * @param array $moderatorroleids
     * @param array $userroleids
     * @param context_course $coursecontext
     * @throws dml_exception
     */
    public static function synchronize_group($rocketchatid, $moodlemembers,
        $moderatorroleids, $userroleids, context_course $coursecontext): void {
        $rocketchatapimanager = new rocket_chat_api_manager();
        $rocketchatmembers = $rocketchatapimanager->get_enriched_group_members_with_moderators(
            $rocketchatid);

        foreach ($moodlemembers as $moodlemember) {
            $rocketchatuser = self::synchronize_rocketchat_member($rocketchatid,
                $coursecontext,
                $moodlemember,
                $moderatorroleids,
                $userroleids,
                $rocketchatmembers);
            if (!empty($rocketchatuser)) {
                unset($rocketchatmembers[$rocketchatuser->username]);
            }
        }
        // Remove remaining Rocket.Chat members no more enrolled in course.
        foreach ($rocketchatmembers as $rocketchatmember) {
            // Prevent moodle Rocket.Chat account unenrolment.
            if ($rocketchatmember->username != get_config('mod_rocketchat', 'apiuser')
                && $rocketchatmember->id != get_config('mod_rocketchat', 'apiuser')) {
                $rocketchatapimanager->unenrol_user_from_group($rocketchatid, $rocketchatmember);
            }
        }
    }

    /**
     * @param $moodleuser
     * @param array $rocketchatmembers
     * @param array $moderatorroleids
     * @param context_course $coursecontext
     * @param rocket_chat_api_manager $rocketchatapimanager
     * @param $rocketchatid
     * @param array $userroleids
     * @return array
     */
    private static function synchronize_rocketchat_member($rocketchatid, $coursecontext, $moodleuser, $moderatorroleids,
        $userroleids, $rocketchatmembers) {
        $rocketchatapimanager = new rocket_chat_api_manager();
        $rocketchatusername = self::rocketchat_username($moodleuser->username);
        $rocketchatuser = null;
        if (array_key_exists($rocketchatusername, $rocketchatmembers)) {
            $rocketchatuser = $rocketchatmembers[$rocketchatusername];
            $ismoderator = self::has_rocket_chat_moderator_role($moderatorroleids, $moodleuser, $coursecontext);
            if ($ismoderator != $rocketchatuser->ismoderator) {
                if ($ismoderator) {
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $moodleuser);
                } else {
                    $rocketchatapimanager->revoke_moderator_in_group($rocketchatid, $moodleuser);
                }
            }
            if (!$ismoderator) {
                // Maybe not a user.
                $isuser = self::has_rocket_chat_user_role($userroleids, $moodleuser, $coursecontext);
                if (!$isuser) {
                    // Unenrol.
                    $rocketchatapimanager->unenrol_user_from_group($rocketchatid, $moodleuser);
                }
            }
        } else {
            // If not exits yet need to create user in Rocket.Chat.
            if ($rocketchatapimanager->user_exists($rocketchatusername)) {
                $rocketchatuser = $rocketchatapimanager->get_user_infos($rocketchatusername);
            } else if ( get_config('mod_rocketchat', 'create_user_account_if_not_exists')) {
                try {
                    $rocketchatuser = $rocketchatapimanager->create_user_if_not_exists($moodleuser);
                } catch (RocketChatException $e) {
                    rocket_chat_api_manager::moodle_debugging_message(
                        "Error while creating user in Rocket.Chat remote group $moodleuser->username", $e, DEBUG_ALL
                    );
                }
            } else {
                return null;
            }
            $isuser = self::has_rocket_chat_user_role($userroleids, $moodleuser, $coursecontext);
            if ($isuser) {
                try {
                    $rocketchatapimanager->enrol_user_to_group($rocketchatid, $moodleuser);
                } catch ( RocketChatException $e) {
                    rocket_chat_api_manager::moodle_debugging_message(
                        "Error while enrolling moderator $moodleuser->username", $e, DEBUG_ALL
                    );
                }
            }
            $ismoderator = self::has_rocket_chat_moderator_role($moderatorroleids, $moodleuser, $coursecontext);
            if ($ismoderator) {
                try {
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $moodleuser);
                } catch ( RocketChatException $e) {
                    rocket_chat_api_manager::moodle_debugging_message(
                        "Error while enrolling moderator $moodleuser->username", $e, DEBUG_ALL
                    );
                }
            }
        }
        return $rocketchatuser;
    }

    /**
     * @param moodle_database|null $DB
     * @return false|mixed
     * @throws dml_exception
     */
    private static function course_enrolments($userid) {
        global $DB;
        $sql = 'select distinct r.rocketchatid, r.moderatorroles, r.userroles, cm.course as courseid from {course_modules} cm'
            . ' inner join {rocketchat} r on cm.instance=r.id'
            . ' inner join {modules} m on m.id=cm.module inner join {enrol} e on e.courseid=cm.course'
            . ' inner join {user_enrolments} ue on ue.enrolid=e.id'
            . ' where m.name=:modulename and m.visible=1 and ue.userid=:userid and cm.visible=1';
        $courseenrolments = $DB->get_records_sql($sql, array('userid' => $userid,
                'modulename' => 'rocketchat')
        );
        return $courseenrolments;
    }

    public static function create_rocketchat_room($moduleid, $course, $rocketchatapimanager) {
        global $DB;
        $groupname = self::rocketchat_group_name($moduleid, $course);
        $groupid = $rocketchatapimanager->create_rocketchat_group($groupname);
        $rocketchat = $DB->get_record('rocketchat', array('id' => $moduleid));
        $rocketchat->rocketchatid = $groupid;
        $DB->update_record('rocketchat', $rocketchat);
        // Need to enrol users.
        // Course information to fit ton function needs.
        $rocketchat->course = $course->id;
        self::enrol_all_concerned_users_to_rocketchat_group($rocketchat,
            get_config('mod_rocketchat', 'background_restore'));

        if ((boolean)get_config('mod_rocketchat', 'retentionfeature')) {
            $retentionsettings = array(
                'retentionenabled' =>
                    property_exists($rocketchat, 'retentionenabled') ? $rocketchat->retentionenabled : false,
                'maxage' => $rocketchat->maxage,
                'filesonly' => property_exists($rocketchat, 'filesonly') ? $rocketchat->filesonly : false,
                'excludepinned' => property_exists($rocketchat, 'excludepinned') ? $rocketchat->excludepinned : false
            );
            $rocketchatapimanager->save_rocketchat_group_settings($rocketchat->rocketchatid, $retentionsettings);
        }
        return $groupid;
    }
}
