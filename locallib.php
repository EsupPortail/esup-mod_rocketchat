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

    public static function enrol_all_concerned_users_to_rocketchat_group($rocketchatmoduleinstance, $background=false) {
        $courseid = $rocketchatmoduleinstance->course;
        $coursecontext = context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext);
        foreach ($users as $user) {
            if ($background) {
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
            } else {
                self::enrol_user_to_rocketchat_group($rocketchatmoduleinstance->rocketchatid,
                    $rocketchatmoduleinstance->moderatorroles,
                    $rocketchatmoduleinstance->userroles,
                    $user->id,
                    $coursecontext->id);
            }
        }
    }

    public static function synchronize_group_members($rocketchatmoduleinstance, $background = false) {
        global $DB;
        if (!is_object($rocketchatmoduleinstance)) {
            $rocketchatmoduleinstanceid = $rocketchatmoduleinstance;
            $rocketchatmoduleinstance = $DB->get_record('rocketchat', array('rocketchatid' => $rocketchatmoduleinstance));
            if (!$rocketchatmoduleinstance) {
                print_error("can't load rocketchat instance $rocketchatmoduleinstanceid in moodle");
            }
        }
        $courseid = $rocketchatmoduleinstance->course;
        $coursecontext = context_course::instance($courseid);
        $moodlemembers = get_enrolled_users($coursecontext);
        $rocketchatid = $rocketchatmoduleinstance->rocketchatid;
        $moderatorroles = $rocketchatmoduleinstance->moderatorroles;
        $moderatorroleids = explode(',', $moderatorroles);
        $userroles = $rocketchatmoduleinstance->userroles;
        $userroleids = explode(',', $userroles);
        if ($background) {
            $tasksynchronize = new \mod_rocketchat\task\synchronize_group();
            $tasksynchronize->set_custom_data(
                array(
                    'rocketchatid' => $rocketchatmoduleinstance->rocketchatid,
                    'moodlemembers' => $moodlemembers,
                    'moderatorrolesids' => $rocketchatmoduleinstance->moderatorroles,
                    'userrolesids' => $rocketchatmoduleinstance->userroles,
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
            print_error('course not found');
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
            print_error('the given cmid is not corresponding with a rocket.chat module');
        }
        self::synchronize_group_members($rocketchat->rocketchatid);
    }

    public static function rocketchat_enabled() {

        global $DB;
        $module = $DB->get_record('modules', array('name' => 'rocketchat'));
        if (!empty($module->visible)) {
            $config = get_config('mod_rocketchat');
            if (!empty($config->instanceurl) && !empty($config->restapiroot)
                && !empty($config->apiuser) && !empty($config->apipassword)) {
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
            print_error(get_string('rcgrouperror', 'mod_rocketchat', $re->getCode()));
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
        $groupname =
            preg_replace(get_config('mod_rocketchat', 'validationgroupnameregex'), '_', $groupname);
        if (empty($groupname)) {
            print_error('sanitized Rocket.Chat groupname can\'t be empty');
        }
        return $groupname;
    }


    public static function  synchronize_user_enrolments($userid) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userid));
        if (!$user) {
            print_error("user $userid not found");
        }
        // Retrieve rocketchat instances where user is supposed to be enrolled.
        // WIP.
    }

    /**
     * @param array $userroleids
     * @param $moodlemember
     * @param context_course $coursecontext
     * @return array
     */
    protected static function has_rocket_chat_user_role(array $userroleids, $moodlemember, context_course $coursecontext) {
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
    public static function role_assign(\context $context, int $roleid, $moodleuser): void {
        $rocketchatapimanager = null;
        if ($context->contextlevel == CONTEXT_COURSE && is_enrolled($context, $moodleuser->id)) {
            $courseid = $context->instanceid;
            // Search for rocketchat module instances concerned.
            $rocketchatmoduleinstances = self::get_rocketchat_module_instances($courseid);
            if (!empty($rocketchatmoduleinstances)) {
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

    /**
     * @param \context $context
     * @param int $roleid
     * @param $moodleuser
     */
    public static function role_unassign(\context $context, int $roleid, $moodleuser): void {
        $courseid = $context->instanceid;
        if ($context->contextlevel == CONTEXT_COURSE) {
            $rocketchatmoduleinstances = self::get_rocketchat_module_instances($courseid);
            if (!empty($rocketchatmoduleinstances)) {
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

    /**
     * @param array $moderatorroleids
     * @param $moodlemember
     * @param context_course $coursecontext
     * @return array
     */
    protected static function has_rocket_chat_moderator_role(array $moderatorroleids, $moodlemember,
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
            $moderatorroleids = explode(',', $moderatorroles);
            $ismoderator = false;
            foreach ($moderatorroleids as $moderatorroleid) {
                if (user_has_role_assignment($userid, $moderatorroleid, $coursecontextid)) {
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $user);
                }
            }
            if (!$ismoderator) {
                $userroleids = explode(',', $userroles);
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
            // Is even in Rocket.Chat.

            $rocketchatusername = self::rocketchat_username($moodlemember->username);
            if (array_key_exists($rocketchatusername, $rocketchatmembers)) {
                $rocketchatmember = $rocketchatmembers[$rocketchatusername];
                $ismoderator = self::has_rocket_chat_moderator_role($moderatorroleids, $moodlemember, $coursecontext);
                if ($ismoderator != $rocketchatmember->ismoderator) {
                    if ($ismoderator) {
                        $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $moodlemember);
                    } else {
                        $rocketchatapimanager->revoke_moderator_in_group($rocketchatid, $moodlemember);
                    }
                }
                if (!$ismoderator) {
                    // Maybe not a user.
                    $isuser = self::has_rocket_chat_user_role($userroleids, $moodlemember, $coursecontext);
                    if (!$isuser) {
                        // Unenrol.
                        $rocketchatapimanager->unenrol_user_from_group($rocketchatid, $moodlemember);
                    }
                }
            } else {
                $isuser = self::has_rocket_chat_user_role($userroleids, $moodlemember, $coursecontext);
                if ($isuser) {
                    $rocketchatapimanager->enrol_user_to_group($rocketchatid, $moodlemember);
                }
                $ismoderator = self::has_rocket_chat_moderator_role($moderatorroleids, $moodlemember, $coursecontext);
                if ($ismoderator) {
                    $rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $moodlemember);
                }
            }
            unset($rocketchatmembers[$rocketchatusername]);
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
}