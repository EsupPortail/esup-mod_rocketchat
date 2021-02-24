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
 * privacy provider file
 * @package     mod_rocketchat
 * @category    event
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Céline Pervès <cperves@unistra.fr>
 */

namespace mod_rocketchat\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider
{
    public static function get_metadata(collection $collection) : collection {
        $collection->add_external_location_link(
            'rocket.chat.server',
            [
                'username' => 'privacy:metadata:mod_rocketchat:rocket_chat_server:username',
                'firstname' => 'privacy:metadata:mod_rocketchat:rocket_chat_server:firstname',
                'lastname' => 'privacy:metadata:mod_rocketchat:rocket_chat_server:lastname',
                'email' => 'privacy:metadata:mod_rocketchat:rocket_chat_server:email',
                'rocketchatids' => 'privacy:metadata:mod_rocketchat:rocket_chat_server:email'
            ],
            'privacy:metadata:mod_rocketchat:rocket_chat_server'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     * @param int $userid
     * @return contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        // Module context.
        $contextlist = new contextlist();

        $sql = 'select cm.id,ctx.id as contextid,r.moderatorroles, r.userroles'
            .' from {course_modules} cm inner join {modules} m on m.id=cm.module'
            .' inner join {rocketchat} r on r.id=cm.instance'
            .' inner join {context} ctx on ctx.instanceid=cm.id and ctx.contextlevel=:contextmodule'
            . ' inner join {enrol} e on e.courseid=cm.course inner join  {user_enrolments} ue on ue.enrolid=e.id'
            .' where m.name=:modname and ue.userid=:userid';
        // Can't filter directly by role since database request are different for string_to_array postgres.
        // So will make it in a second time.
        $params = array(
            'modname' => 'rocketchat',
            'contextmodule' => CONTEXT_MODULE,
            'userid' => $userid
        );
        $records = $DB->get_records_sql($sql, $params);
        // Filter depending of role.
        $ctxids = array();
        foreach ($records as $record) {
            $roles = array();
            $roles = array_merge($roles, array_filter(explode(',', $record->moderatorroles)));
            $roles = array_merge($roles, array_filter(explode(',', $record->userroles)));
            foreach ($roles as $roleid) {
                if (user_has_role_assignment($userid, $roleid, $record->contextid )) {
                    $ctxids[$record->contextid] = $record->contextid;
                }
            }
        }
        // Fake request.
        if (count($ctxids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal(array_values($ctxids), SQL_PARAMS_NAMED);
            $contextlist->add_from_sql('select distinct id from {context} where id '.$insql, $inparams);
        }
        return $contextlist;
    }

    /**
     *  Get the list of users who have data within a context.
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_module) {
            // Check this is rocketchat module.
            $rocketchat = $DB->get_record_sql('select r.* from {rocketchat} r inner join {course_modules} cm on cm.instance=r.id'
                .' inner join {modules} m on m.id=cm.module where cm.id=:cmid', array('cmid' => $context->instanceid));
            if ($rocketchat) {
                list($moderatorrolesinsql, $moderatorrolesinparams) =
                    $DB->get_in_or_equal(array_filter(explode(',', $rocketchat->moderatorroles)), SQL_PARAMS_NAMED);
                list($userrolesinsql, $userrolesinparams) =
                    $DB->get_in_or_equal(array_filter(explode(',', $rocketchat->userroles)), SQL_PARAMS_NAMED);
                $sql = 'select ra.userid,cm.id,ctx.id as contextid,r.moderatorroles, r.userroles'
                    .' from {course_modules} cm inner join {modules} m on m.id=cm.module'
                    .' inner join {rocketchat} r on r.id=cm.instance'
                    .' inner join {context} ctx on ctx.instanceid=cm.course and ctx.contextlevel=:contextcourse'
                    . ' inner join {enrol} e on e.courseid=cm.course inner join  {user_enrolments} ue on ue.enrolid=e.id'
                    . ' inner join {role_assignments} ra'
                        .' on ra.contextid=ctx.id and ra.userid=ue.userid'
                        .' and (ra.roleid '.$moderatorrolesinsql.' or ra.roleid '.$userrolesinsql.')'
                .' where m.name=:modname and cm.id=:cmid';
                $params = $moderatorrolesinparams + $userrolesinparams + [
                    'contextcourse' => CONTEXT_COURSE,
                    'modname' => 'rocketchat',
                    'cmid' => $context->instanceid
                ];
                $userlist->add_from_sql('userid', $sql, $params);
            }
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $contextlist->get_user()->id;
        $contexts = $contextlist->get_contexts();
        foreach ($contexts as $context) {
            if ($context instanceof \context_module) {
                $rocketchat = $DB->get_record_sql('select r.* from {rocketchat} r'
                    .' inner join {course_modules} cm on cm.instance=r.id'
                    .' inner join {modules} m on m.id=cm.module where cm.id=:cmid', array('cmid' => $context->instanceid));
                if ($rocketchat) {
                    list($moderatorrolesinsql, $moderatorrolesinparams) =
                        $DB->get_in_or_equal(array_filter(explode(',', $rocketchat->moderatorroles)), SQL_PARAMS_NAMED);
                    list($userrolesinsql, $userrolesinparams) =
                        $DB->get_in_or_equal(array_filter(explode(',', $rocketchat->userroles)), SQL_PARAMS_NAMED);
                    $sql = 'select distinct r.rocketchatid'
                        .' from {course_modules} cm inner join {modules} m on m.id=cm.module'
                        .' inner join {rocketchat} r on r.id=cm.instance'
                        .' inner join {context} ctx on ctx.instanceid=cm.course and ctx.contextlevel=:contextcourse'
                        .' inner join {enrol} e on e.courseid=cm.course inner join {user_enrolments} ue on ue.enrolid=e.id'
                        .' inner join {role_assignments} ra'
                        .' on ra.contextid=ctx.id and ra.userid=ue.userid'
                        .' and (ra.roleid '.$moderatorrolesinsql.' or ra.roleid '.$userrolesinsql.')'
                        .' where m.name=:modname and cm.id=:cmid and ue.userid=:userid';
                    $params = $moderatorrolesinparams + $userrolesinparams + [
                        'contextcourse' => CONTEXT_COURSE,
                        'modname' => 'rocketchat',
                        'cmid' => $context->instanceid,
                        'userid' => $userid
                    ];
                    $entry = $DB->get_record_sql($sql, $params);
                    $data = new \stdClass();
                    $data->username = $user->username;
                    $data->firstname = $user->firstname;
                    $data->lastname = $user->lastname;
                    $data->email = $user->email;
                    $data->rocketchatid = $entry->rocketchatid;
                    writer::with_context($context)->export_data(
                        [
                            get_string('pluginname', 'mod_rocketchat'),
                            get_string('datastransmittedtorc', 'mod_rocketchat')
                        ],
                        (object)['transmitted_to_rocket_chat' => $data]
                    );
                }
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // External datas so no deletion.

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // External datas so no deletion.

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // External datas so no deletion.
    }
}
