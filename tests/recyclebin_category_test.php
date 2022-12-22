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
 * mod_rocketchat category recyclebin tests.
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

class recyclebin_category_test extends advanced_testcase{

    private $user;
    private $rocketchat;
    private $course;

    protected function setUp() : void {
        global $CFG, $DB;
        parent::setUp();
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        set_config('background_add_instance', 0, 'mod_rocketchat');

    }

    public function test_course_deletion_with_recyclebin() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('categorybinenable', 1, 'tool_recyclebin');
        set_config('categorybinexpiry', 1, 'tool_recyclebin');
        set_config('background_restore', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        ob_start(); // Prevent echo output for tests.
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertNotEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        $rocketchatmanager->delete_user($this->user->username);
        phpunit_util::run_all_adhoc_tasks(); // Just in case of plugin taht trigger this behaviour.
        // Empty recycle bin.
        ob_start();
        $task = new \tool_recyclebin\task\cleanup_category_bin();
        $task->execute();
        ob_get_contents();
        ob_end_clean();
        // Cross rocketchat tablemust me empty.
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertFalse($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertDebuggingCalled();
    }


    public function test_course_deletion_without_recyclebin() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('categorybinenable', 0, 'tool_recyclebin');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks(); // Just in case of plugin taht trigger this behaviour.
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertFalse($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertDebuggingCalled();
        $rocketchatmanager->delete_user($this->user->username);

    }

    public function test_course_restoration_with_recyclebin() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('categorybinenable', 1, 'tool_recyclebin');
        set_config('categorybinexpiry', 1, 'tool_recyclebin');
        set_config('background_restore', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks();
        $rocketchatmanager = new rocket_chat_api_manager();
        // Remote Rocket.Chat private group exists and is archived.
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertNotEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        // Restore from recycle bin.
        ob_start();
        // Try restoring.
        $recyclebin = new \tool_recyclebin\category_bin($this->course->category);
        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->restore_item($item);
        }
        ob_get_contents();
        ob_end_clean();
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group exists.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertFalse($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->rocketchat->rocketchatid));
        // Clean Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($this->user->username);
    }
    public function test_course_deletion_with_recyclebin_without_patch() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        // Now, run the course module deletion adhoc task.
        ob_start(); // Prevent echo output for tests.
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        // Time to empty recycle bin.
        ob_start();
        $task = new \tool_recyclebin\task\cleanup_category_bin();
        $task->execute();
        ob_get_contents();
        ob_end_clean();
        // Cross rocketchat tablemust me empty.
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group not deleted and archived.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        // Can't check group members since group is archived.
        // Clean remote Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($this->user->username);
    }
    public function test_deletion_without_recyclebin_without_patch() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin no to be enabled.
        set_config('categorybinenable', 0, 'tool_recyclebin');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks(); // Just in case of plugin taht trigger this behaviour.
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertFalse($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertDebuggingCalled();
        $rocketchatmanager->delete_user($this->user->username);

    }
    public function test_restoration_with_recyclebin_without_patch() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('categorybinenable', 1, 'tool_recyclebin');
        $this->set_up_moodle_datas();
        delete_course($this->course->id, false);
        // Now, run the course module deletion adhoc task.
        phpunit_util::run_all_adhoc_tasks();
        $rocketchatmanager = new rocket_chat_api_manager();
        // Remote Rocket.Chat private group exists and is archived.
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        $rocketchatrecord = $DB->get_record('rocketchat', array('id' => $this->rocketchat->id));
        $this->assertEmpty($rocketchatrecord);
        // Restore from recycle bin.
        ob_start();
        // Try restoring.
        $recyclebin = new \tool_recyclebin\category_bin($this->course->category);
        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->restore_item($item);
        }
        ob_get_contents();
        ob_end_clean();
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group exists.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));
        // Clean Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($this->user->username);
    }

    protected function set_up_moodle_datas() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $username = 'moodleusertest' . time();
        $this->user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->user->id, $this->course->id, $student->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_' . time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
}
