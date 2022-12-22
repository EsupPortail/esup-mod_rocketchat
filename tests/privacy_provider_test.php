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
 * Data provider tests.
 *
 * @package    logstore_last_viewed_course_module
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_userlist;
use mod_rocketchat\privacy\provider;
use \core_privacy\local\request\userlist;
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

require_once($CFG->libdir . '/tests/fixtures/events.php');

class privacy_provider_test extends \core_privacy\tests\provider_testcase {


    private $course1;
    private $course2;
    private $rocketchat1;
    private $rocketchat2;
    private $rocketchatcontext1;
    private $rocketchatcontext2;
    private $module2;
    private $userstudent;
    private $usereditingteacher;

    public function setUp() : void {
        global $DB, $CFG;
        parent::setUp();
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course();
        $this->course2 = $generator->create_course();
        $studentusername = 'moodleusertest'.time();
        $this->userstudent = $generator->create_user(array('username' => $studentusername,
            'firstname' => $studentusername, 'lastname' => $studentusername));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->userstudent->id, $this->course1->id, $student->id);
        $generator->enrol_user($this->userstudent->id, $this->course2->id, $student->id);
        $edititingteacherusername = 'moodleusertest'.(time() + 1);
        $this->usereditingteacher = $generator->create_user(array('username' => $edititingteacherusername,
            'firstname' => $edititingteacherusername, 'lastname' => $edititingteacherusername));
        $editingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $generator->enrol_user($this->usereditingteacher->id, $this->course1->id, $editingteacher->id);
        $generator->enrol_user($this->usereditingteacher->id, $this->course2->id, $editingteacher->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course1);
        $this->rocketchat1 = $generator->create_module('rocketchat',
            array('course' => $this->course1->id, 'groupname' => $groupname));
        $groupname = mod_rocketchat_tools::rocketchat_group_name(1, $this->course1);
        $this->rocketchat2 = $generator->create_module('rocketchat',
            array('course' => $this->course1->id, 'groupname' => $groupname));
        $this->rocketchatcontext1 = context_module::instance($this->rocketchat1->cmid);
        $this->rocketchatcontext2 = context_module::instance($this->rocketchat2->cmid);
    }

    public function tearDown() : void {
        ob_start();
        if (!empty($this->rocketchat1)) {
            course_delete_module($this->rocketchat1->cmid, true);
        }
        if (!empty($this->rocketchat1)) {
            course_delete_module($this->rocketchat2->cmid, true);
        }
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatmanager->delete_user($this->userstudent->username);
        $rocketchatmanager->delete_user($this->usereditingteacher->username);
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat1->rocketchatid);
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat2->rocketchatid);
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }

    /**
     * test get_users_in_context function
     */
    public function test_get_users_in_context() {
        // Setup in setUp function.
        $userlist = new userlist($this->rocketchatcontext1, 'mod_rocketchat');
        provider::get_users_in_context($userlist);
        $users = $userlist->get_users();
        $this->assertCount(2, $users);
        $this->assertTrue(in_array($this->usereditingteacher, $users));
        $this->assertTrue(in_array($this->userstudent, $users));
    }

    /**
     * Tets get_contexts_for_userid function.
     * Function that get the list of contexts that contain user information for the specified user.
     * @throws coding_exception
     */
    public function test_user_contextlist() {
        $contextlist = provider::get_contexts_for_userid($this->userstudent->id);
        $this->assertCount(2, $contextlist->get_contexts());
        $this->assertContains($this->rocketchatcontext1, $contextlist->get_contexts());
        $this->assertContains($this->rocketchatcontext2, $contextlist->get_contexts());
    }

    /**
     * Test export_all_data_for_user function.
     * funciton that export all data for a component for the specified user.
     * @throws coding_exception
     */
    public function test_export_user_data() {
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            $this->userstudent,
            'mod_rocketchat',
            [$this->rocketchatcontext1->id, $this->rocketchatcontext2->id]
        );
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->rocketchatcontext1);
        $data = $writer->get_data([get_string('pluginname', 'mod_rocketchat'),
            get_string('datastransmittedtorc', 'mod_rocketchat')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'transmitted_to_rocket_chat'));
        $this->assertInstanceOf('stdClass', $data->transmitted_to_rocket_chat);
        $this->assertEquals($this->userstudent->username, $data->transmitted_to_rocket_chat->username);
        $this->assertEquals($this->rocketchat1->rocketchatid, $data->transmitted_to_rocket_chat->rocketchatid);

        \core_privacy\local\request\writer::reset();
        provider::export_user_data($approvedcontextlist);
        $writer = \core_privacy\local\request\writer::with_context($this->rocketchatcontext2);
        $data = $writer->get_data([get_string('pluginname', 'mod_rocketchat'),
            get_string('datastransmittedtorc', 'mod_rocketchat')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'transmitted_to_rocket_chat'));
        $this->assertInstanceOf('stdClass', $data->transmitted_to_rocket_chat);
        $this->assertEquals($this->userstudent->username, $data->transmitted_to_rocket_chat->username);
        $this->assertEquals($this->rocketchat2->rocketchatid, $data->transmitted_to_rocket_chat->rocketchatid);
    }
}
