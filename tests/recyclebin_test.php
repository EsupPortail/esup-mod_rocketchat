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
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

class recyclebin_testcase extends advanced_testcase{

    protected function setUp() {
        global $CFG, $DB;
        parent::setUp();
        // Enable rocketchat module
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_deletion_with_recyclebin() {
        global $DB;
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry',1,'tool_recyclebin');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $username = 'moodleusertest'.time();
        $user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($user->id, $course->id, $student->id);
        // TODO if possible, try to create a mock that take in charge inner new rocket_chat_api_manager() call
        //set a groupname for tests
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $course);
        $rocketchat = $generator->create_module('rocketchat',
            array('course' => $course->id, 'groupname' => $groupname));
        course_delete_module($rocketchat->cmid, true);
        // Now, run the course module deletion adhoc task.
        ob_start(); // Prevent echo output for tests.
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $rocketchatmanager = new rocket_chat_api_manager();
        $group = $rocketchatmanager->get_rocketchat_group_object($rocketchat->rocketchatid, '');
        $this->assertNotEmpty($group);
        $groupinfo = $group->info();
        $this->assertNotEmpty($groupinfo);
        $this->assertTrue($groupinfo->group->archived);
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $rocketchat->rocketchatid));
        $this->assertNotEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        $rocketchatmanager->delete_user($user->username);
        //time to empty recycle bin
        ob_start();
        $task = new \tool_recyclebin\task\cleanup_course_bin();
        $task->execute();
        ob_get_contents();
        ob_end_clean();
        // Cross rocketchat tablemust me empty.
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted.
        $rocketchatmanager = new rocket_chat_api_manager();
        $group = $rocketchatmanager->get_rocketchat_group_object($rocketchat->rocketchatid, '');
        $this->assertNotEmpty($group);
        $groupinfo = $group->info();
        $this->assertEmpty($groupinfo);
    }

    public function test_deletion_without_recyclebin() {
        global $DB;
        // We want the category bin to be enabled.
        set_config('coursebinenable', 0, 'tool_recyclebin');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $username = 'moodleusertest'.time();
        $user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($user->id, $course->id, $student->id);
        // TODO if possible, try to create a mock that take in charge inner new rocket_chat_api_manager() call
        //set a groupname for tests
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $course);
        $rocketchat = $generator->create_module('rocketchat',
            array('course' => $course->id, 'groupname' => $groupname));
        course_delete_module($rocketchat->cmid, true);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks(); // Just in case of plugin taht trigger this behaviour.
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted
        $rocketchatmanager = new rocket_chat_api_manager();
        $group = $rocketchatmanager->get_rocketchat_group_object($rocketchat->rocketchatid, '');
        $this->assertNotEmpty($group);
        $groupinfo = $group->info();
        $this->assertEmpty($groupinfo);
        $rocketchatmanager->delete_user($user->username);

    }

    public function test_restoration_with_recyclebin() {
        global $DB;
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry',1,'tool_recyclebin');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $username = 'moodleusertest'.time();
        $user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($user->id, $course->id, $student->id);
        // TODO if possible, try to create a mock that take in charge inner new rocket_chat_api_manager() call
        //set a groupname for tests
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $course);
        $rocketchat = $generator->create_module('rocketchat',
            array('course' => $course->id, 'groupname' => $groupname));
        course_delete_module($rocketchat->cmid, true);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks();
        $rocketchatmanager = new rocket_chat_api_manager();
        // Remote Rocket.Chat private group exists and is archived
        $group = $rocketchatmanager->get_rocketchat_group_object($rocketchat->rocketchatid, '');
        $this->assertNotEmpty($group);
        $groupinfo = $group->info();
        $this->assertTrue($groupinfo->group->archived);
        $this->assertNotEmpty($groupinfo);
        $this->assertTrue($groupinfo->group->archived);
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $rocketchat->rocketchatid));
        $this->assertNotEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        // Restore from recycle bin.
        ob_start();
        // Try restoring.
        $recyclebin = new \tool_recyclebin\course_bin($course->id);
        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->restore_item($item);
        }
        ob_get_contents();
        ob_end_clean();
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group exists
        $rocketchatmanager = new rocket_chat_api_manager();
        $group = $rocketchatmanager->get_rocketchat_group_object($rocketchat->rocketchatid, '');
        $this->assertNotEmpty($group);
        $groupinfo = $group->info();
        $this->assertNotEmpty($groupinfo);
        $this->assertFalse($groupinfo->group->archived);
        // Clean Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($user->username);
    }
}