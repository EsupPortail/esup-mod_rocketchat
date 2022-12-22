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
 * mod_rocketchat rest api manager tests.
 *
 * @package    local_digital_training_account_services
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../locallib.php');
global $CFG;

require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');

class background_enrolments_test extends advanced_testcase{
    private $rocketchatapimanager;
    private $course;
    private $user;
    private $user2;
    private $datagenerator;
    private $rocketchat;
    private $studentrole;
    private $editingteacherrole;

    public function setUp() : void {
        global $DB;
        parent::setUp();
        $this->initiate_test_environment();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        // User Creation mode.
        set_config('create_user_account_if_not_exists', 1, 'mod_rocketchat');
        $this->datagenerator = $this->getDataGenerator();
        $this->course = $this->datagenerator->create_course();
        $username = 'moodleusertest'.time();
        $this->user = $this->datagenerator->create_user(
            array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $this->user2 = $this->datagenerator->create_user(
            array('username' => $username.'2', 'firstname' => $username.'2', 'lastname' => $username.'2'));
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
    }

    protected function tearDown() : void {
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        $this->rocketchatapimanager->delete_user($this->user->username);
        parent::tearDown();
    }


    public function test_enrol_unenrol_user_no_background() {
        // No enrolment method in background.
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        $this->create_rocketchat_module();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members); // Only Owner.
        $this->datagenerator->enrol_user($this->user->id, $this->course->id, $this->studentrole->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        $enrolmethod = 'manual';
        self::unenrol_user($enrolmethod, $this->course->id, $this->user->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
    }

    public function test_enrol_unenrol_user_manual_background() {
        set_config('background_enrolment_task', 'enrol_manual', 'mod_rocketchat');
        $this->create_rocketchat_module();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members); // Owner.
        $this->datagenerator->enrol_user($this->user->id, $this->course->id, $this->studentrole->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
        // Need to trigger adhoc tasks to enrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        $enrolmethod = 'manual';
        self::unenrol_user($enrolmethod, $this->course->id, $this->user->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        // Need to trigger adhoc tasks to unenrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
    }

    public function test_enrol_unenrol_user_cohort_background() {
        $this->create_rocketchat_module();
        self::enable_cohort_enrolments();
        set_config('background_enrolment_task', 'enrol_cohort', 'mod_rocketchat');
        $trace = new null_progress_trace();
        $cohort = $this->datagenerator->create_cohort(array('context' => context_system::instance()));
        $plugin = enrol_get_plugin('cohort');
        // Create a course.
        // Enable this enrol plugin for the course.
        $plugin->add_instance($this->course, array(
                'customint1' => $cohort->id,
                'roleid' => $this->studentrole->id)
        );
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members); // Owner.
        cohort_add_member($cohort->id, $this->user->id);
        enrol_cohort_sync($trace, $this->course->id);
        $this->datagenerator->enrol_user($this->user2->id, $this->course->id, $this->studentrole->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members); // Owner.and user2
        // Need to trigger adhoc tasks to enrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(3, $members);
        $enrolmethod = 'cohort';
        self::unenrol_user($enrolmethod, $this->course->id, $this->user->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(3, $members);
        // Need to trigger adhoc tasks to unenrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        self::unenrol_user('manual', $this->course->id, $this->user2->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
    }

    public function test_user_role_changes_override_module_context() {
        $this->create_rocketchat_module();
        set_config('background_enrolment_task', 'enrol_manual', 'mod_rocketchat');
        $modulecontext = context_module::instance($this->rocketchat->cmid);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members); // Owner.
        $this->datagenerator->enrol_user($this->user->id, $this->course->id, $this->studentrole->id);
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
        // Need to trigger adhoc tasks to enrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        $enrolmethod = 'manual';
        // Assign editingteacher role.
        role_assign($this->editingteacherrole->id, $this->user->id, $modulecontext->id);
        $moderators = $this->rocketchatapimanager->get_group_moderators($this->rocketchat->rocketchatid);
        $this->assertCount(0, $moderators);
        // Trigger adhoc tasks.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $moderators = $this->rocketchatapimanager->get_group_moderators($this->rocketchat->rocketchatid);
        $this->assertCount(1, $moderators);
        $this->assertCount(2, $members);
        // Unassign editingteacher role in module context.
        role_unassign($this->editingteacherrole->id, $this->user->id, $modulecontext->id);
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $moderators = $this->rocketchatapimanager->get_group_moderators($this->rocketchat->rocketchatid);
        $this->assertCount(0, $moderators);
        $this->assertCount(2, $members);
    }

    public function test_add_instance_enrol_user_manual_background_currentuser() {
        set_config('background_enrolment_task', 'enrol_manual', 'mod_rocketchat');
        // Create a new rocketchat instance after course enrolments.
        $this->datagenerator->enrol_user($this->user->id, $this->course->id, $this->editingteacherrole->id);
        $this->datagenerator->enrol_user($this->user2->id, $this->course->id, $this->studentrole->id);
        $this->setUser($this->user);
        $this->create_rocketchat_module();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(2, $members); // Owner.
        // Need to trigger adhoc tasks to enrol.
        phpunit_util::run_all_adhoc_tasks();
        $members = $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid);
        $this->assertCount(3, $members);
    }

    private function load_rocketchat_test_config() {
        global $CFG;
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
    }

    private function initiate_test_environment() {
        $this->resetAfterTest(true);
        $this->load_rocketchat_test_config();
    }

    /**
     * @param string $enrolmethod
     * @throws coding_exception
     */
    protected static function unenrol_user($enrolmethod, $courseid, $userid) {
        $enrol = enrol_get_plugin($enrolmethod);
        $enrolinstances = enrol_get_instances($courseid, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == $enrolmethod) {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->unenrol_user($instance, $userid);
    }

    protected static function enable_cohort_enrolments(): void {
        $enabled = enrol_get_plugins(true);
        $enabled['cohort'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    private function create_rocketchat_module(): void {
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $this->datagenerator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
}
