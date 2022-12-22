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
 * mod_rocketchat event observers test.
 * recycle bin tests included into observer_testcase
 * @package    local_digital_training_account_services
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once(__DIR__.'/../locallib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');
use \mod_rocketchat\api\manager\rocket_chat_api_manager;

class course_reset_test extends advanced_testcase{
    private $course;
    private $rocketchat;
    private $userstudent;
    private $usereditingteacher;
    private $rocketchatapimanager;
    private $editingteacherrole;
    private $studentrole;

    protected function setUp() : void {
        global $CFG, $DB;
        parent::setUp();
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $studentusername = 'moodleusertest'.time();
        $this->userstudent = $generator->create_user(array('username' => $studentusername,
            'firstname' => $studentusername, 'lastname' => $studentusername));
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->userstudent->id, $this->course->id, $this->studentrole->id);
        $edititingteacherusername = 'moodleusertest'.(time() + 1);
        $this->usereditingteacher = $generator->create_user(array('username' => $edititingteacherusername,
            'firstname' => $edititingteacherusername, 'lastname' => $edititingteacherusername));
        $this->editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $generator->enrol_user($this->usereditingteacher->id, $this->course->id, $this->editingteacherrole->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
        $this->rocketchatapimanager = new rocket_chat_api_manager();
    }
    protected function tearDown() : void {
        ob_start();
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatmanager->delete_user($this->userstudent->username);
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }

    public function test_course_reset() {
        // Structure created in setUp.

        $this->rocketchatapimanager->post_message($this->rocketchat->rocketchatid, 'a message');
        $this->rocketchatapimanager->post_message($this->rocketchat->rocketchatid, 'a second message');
        $data = new stdClass();
        $data->id = $this->course->id;
        $data->unenrol_users = false;
        $data->reset_rocketchat = false;
        reset_course_userdata($data);
        $this->assertCount(3, $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid));
        $this->assertCount(5, $this->rocketchatapimanager->get_group_messages($this->rocketchat->rocketchatid));
        $data->reset_rocketchat = true;
        reset_course_userdata($data);
        $this->assertCount(3, $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid));
        $this->assertCount(0, $this->rocketchatapimanager->get_group_messages($this->rocketchat->rocketchatid));
        $roles = tool_uploadcourse_helper::get_role_ids();
        $data->unenrol_users = array($this->studentrole->id, $this->editingteacherrole->id);
        reset_course_userdata($data);
        $this->assertCount(1, $this->rocketchatapimanager->get_group_members($this->rocketchat->rocketchatid));
        $this->assertCount(0, $this->rocketchatapimanager->get_group_messages($this->rocketchat->rocketchatid));
    }
}
