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
 * mod_rocketchat_moderator_and_user_roles_test tests.
 *
 * @package    local_digital_training_account_services
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../locallib.php');
global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');
require_once($CFG->dirroot.'/mod/rocketchat/lib.php');

class username_and_bad_caracters_test extends advanced_testcase {
    private $rocketchatapimanager;
    private $studentrole;
    private $editingteacherrole;
    private $datagenerator;

    public function setUp() : void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        $this->initiate_environment_and_connection();
        $this->datagenerator = $this->getDataGenerator();
        set_config('create_user_account_if_not_exists', 1, 'mod_rocketchat');
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
    }

    public function test_create_user_invalid_username() {
        $moodleuser = new stdClass();
        $moodleuser->username = 'belinda@purcell.com'.time();
        $moodleuser->firstname = 'Belinda';
        $moodleuser->lastname = 'ThyHand';
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');
        $this->expectException(\RocketChat\RocketChatException::class);
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_add_rocketchat_member_with_bad_username() {
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moderator = new stdClass();
        $moderator->username = 'usertestmoderator@badname' . time();
        $moderator->firstname = 'moodleusertestModerator';
        $moderator->lastname = $moderator->firstname;
        $moderator->email = $moderator->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $user = new stdClass();
        $user->username = 'usertest@badname' . time();
        $user->firstname = 'moodleusertest';
        $user->lastname = $user->firstname;
        $user->email = $user->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $gooduser = new stdClass();
        $gooduser->username = 'goodusertest' . time();
        $gooduser->firstname = 'goodmoodleusertest';
        $gooduser->lastname = $user->firstname;
        $gooduser->email = $user->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $groupname = 'moodletestgroup' . time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);
        $this->assertTrue($this->rocketchatapimanager->group_exists($groupid));
        $this->waitForSecond(); // Some times seems that Rocket.Chat server is too long.
        // False since not enrolled!
        $this->assertFalse($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $moderator));
        $this->assertDebuggingCalled();
        $this->assertEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $user));
        $this->assertDebuggingCalled();
        $members = $this->rocketchatapimanager->get_enriched_group_members($groupid);
        $this->assertCount(1, $members);
    }

    public function test_synchronize_rocketchat_member_with_bad_username() {
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moderator = new stdClass();
        $moderator->username = 'moodleusertestmoderator@badname' . time();
        $moderator->firstname = 'moodleusertestmoderator';
        $moderator->lastname = $moderator->firstname;
        $moderator->email = $moderator->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $user = new stdClass();
        $user->username = 'moodleusertest@badname' . time();
        $user->firstname = 'moodleusertest';
        $user->lastname = $user->firstname;
        $user->email = $user->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $gooduser = new stdClass();
        $gooduser->username = 'usertest' . time();
        $gooduser->firstname = 'goodmoodleusertest';
        $gooduser->lastname = $user->firstname;
        $gooduser->email = $user->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $groupname = 'moodletestgroup' . time();
        // Create RC module.
        $course = $this->datagenerator->create_course();
        $rocketchat = $this->datagenerator->create_module('rocketchat',
            array('course' => $course->id, 'groupname' => $groupname));
        $this->assertNotEmpty($rocketchat);
        $this->assertTrue($this->rocketchatapimanager->group_exists($rocketchat->rocketchatid));
        $this->waitForSecond(); // Some times seems that Rocket.Chat server is too long.
        // Enroll manually.
        // Create users.
        $user->id = $this->datagenerator->create_user(
            array('username' => $user->username, 'firstname' => $user->firstname, 'lastname' => $user->lastname)
        )->id;
        $moderator->id = $this->datagenerator->create_user(
            array('username' => $moderator->username, 'firstname' => $moderator->firstname, 'lastname' => $moderator->lastname)
        )->id;
        $gooduser->id = $this->datagenerator->create_user(
            array('username' => $gooduser->username, 'firstname' => $gooduser->firstname, 'lastname' => $gooduser->lastname)
        )->id;
        // ...enrol with background task
        set_config('background_enrolment_task', 'enrol_manual', 'mod_rocketchat');
        $this->datagenerator->enrol_user($user->id, $course->id, $this->studentrole->id);
        $this->datagenerator->enrol_user($gooduser->id, $course->id, $this->studentrole->id);
        $this->datagenerator->enrol_user($gooduser->id, $course->id, $this->editingteacherrole->id);
        $members = $this->rocketchatapimanager->get_enriched_group_members($rocketchat->rocketchatid);
        $this->assertCount(1, $members);
        phpunit_util::run_all_adhoc_tasks();
        $this->assertDebuggingCalled();
        $members = $this->rocketchatapimanager->get_enriched_group_members($rocketchat->rocketchatid);
        $this->assertCount(2, $members);
        $this->assertContains($gooduser->username, array_keys($members));

        // Clean before going out!
        if (!empty($rocketchat)) {
            course_delete_module($rocketchat->cmid, true);
        }
        $this->rocketchatapimanager->delete_user($gooduser->username);
    }


    private function load_rocketchat_test_config() {
        global $CFG;
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
    }

    private function initiate_test_environment() {
        $this->resetAfterTest(true);
        $this->load_rocketchat_test_config();
    }

    private function initiate_environment_and_connection() {
        $this->initiate_test_environment();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
    }
}

