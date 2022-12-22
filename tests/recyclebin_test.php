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
require_once($CFG->dirroot.'/enrol/manual/externallib.php');
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

class recyclebin_test extends advanced_testcase{

    private $userstudent1;
    private $userstudent2;
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
    }

    public function test_deletion_with_recyclebin() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
        // Now, run the course module deletion adhoc task.
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
        $rocketchatmanager->delete_user($this->userstudent1->username);
        phpunit_util::run_all_adhoc_tasks(); // Just in case of plugin taht trigger this behaviour.
        // Empty recycle bin.
        ob_start();
        $task = new \tool_recyclebin\task\cleanup_course_bin();
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

    public function test_deletion_without_recyclebin() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 0, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);

    }

    public function test_restoration_with_recyclebin() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        set_config('background_restore', 0, 'mod_rocketchat');
        set_config('background_synchronize', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        // Can't retrieve members from a archived group...
        // So can't test that members number doesn't change.
        // Unenrol a user.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($this->course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->unenrol_user($instance, $this->userstudent2->id);
        // Can't retrieve members from a archived group...
        // So can't test that members number doesn't change.
        // Restore from recycle bin.
        ob_start();
        // Try restoring.
        $recyclebin = new \tool_recyclebin\course_bin($this->course->id);
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
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);
    }

    public function test_restoration_with_recyclebin_with_background() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        set_config('background_restore', 0, 'mod_rocketchat');
        set_config('background_synchronize', 1, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        // Can't retrieve members from a archived group...
        // So can't test that members number doesn't change.
        // Unenrol a user.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($this->course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->unenrol_user($instance, $this->userstudent2->id);
        // Can't retrieve members from a archived group...
        // So can't test that members number doesn't change.
        // Restore from recycle bin.
        ob_start();
        // Try restoring.
        $recyclebin = new \tool_recyclebin\course_bin($this->course->id);
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
        $this->assertCount(3, $rocketchatmanager->get_group_members($this->rocketchat->rocketchatid));
        ob_start();
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->rocketchat->rocketchatid));
        // Clean Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);
    }

    public function test_deletion_with_recyclebin_without_patch() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        $task = new \tool_recyclebin\task\cleanup_course_bin();
        $task->execute();
        ob_get_contents();
        ob_end_clean();
        // Cross rocketchat tablemust me empty.
        $rocketchatxrecyclebin = $DB->get_record('rocketchatxrecyclebin', array('rocketchatid' => $this->rocketchat->rocketchatid));
        $this->assertEmpty($rocketchatxrecyclebin);
        // Remote Rocket.Chat private group is deleted.
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->rocketchat->rocketchatid));
        $this->assertTrue($rocketchatmanager->group_archived($this->rocketchat->rocketchatid));;
        // Clean remote Rocket.Chat.
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);
    }
    public function test_deletion_without_recyclebin_without_patch() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 0, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);

    }
    public function test_restoration_with_recyclebin_without_patch() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        set_config('recyclebin_patch', 0, 'mod_rocketchat');
        // We want the category bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        set_config('coursebinexpiry', 1, 'tool_recyclebin');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
        course_delete_module($this->rocketchat->cmid, true);
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
        $recyclebin = new \tool_recyclebin\course_bin($this->course->id);
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
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_user($this->userstudent2->username);
    }

    protected function set_up_moodle_datas() {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $username = 'moodleusertest' . time();
        $username2 = 'moodleusertest' . (time() + 1);
        $this->userstudent1 = $generator->create_user(array('username' => $username, 'firstname' => $username,
            'lastname' => $username));
        $this->userstudent2 = $generator->create_user(array('username' => $username2, 'firstname' => $username2,
            'lastname' => $username2));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->userstudent1->id, $this->course->id, $student->id);
        $generator->enrol_user($this->userstudent2->id, $this->course->id, $student->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_' . time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
}
