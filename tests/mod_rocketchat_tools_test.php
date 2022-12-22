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
 * mod_rocketchat_tools tests.
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

class mod_rocketchat_tools_test extends advanced_testcase {
    private $rocketchatapimanager;
    private $course;
    private $rocketchat;
    private $module;
    private $student1;
    private $student2;
    private $student3;
    private $teacher1;
    private $teacher2;
    private $teacher3;
    private $generator;

    public function setUp() : void {
        global $DB;
        parent::setUp();
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        $this->initiate_test_environment();
    }

    private function load_rocketchat_test_config() {
        global $CFG;
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
    }

    public function tearDown() : void {
        ob_start();
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        $this->rocketchatapimanager->delete_user($this->student1->username);
        $this->rocketchatapimanager->delete_user($this->student2->username);
        $this->rocketchatapimanager->delete_user($this->student3->username);
        $this->rocketchatapimanager->delete_user($this->teacher1->username);
        $this->rocketchatapimanager->delete_user($this->teacher2->username);
        $this->rocketchatapimanager->delete_user($this->teacher3->username);
        $this->rocketchatapimanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }

    private function initiate_test_environment() {
        global $DB;
        $this->resetAfterTest(true);
        $this->load_rocketchat_test_config();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
        $this->setAdminUser();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $studentusername1 = 'moodleuserteststudent1_'.time();
        $studentusername2 = 'moodleuserteststudent2_'.time();
        $studentusername3 = 'moodleuserteststudent3_'.time();
        $this->student1 = $this->generator->create_user(array('username' => $studentusername1,
            'firstname' => $studentusername1, 'lastname' => $studentusername1));
        $this->student2 = $this->generator->create_user(array('username' => $studentusername2,
            'firstname' => $studentusername2, 'lastname' => $studentusername2));
        $this->student3 = $this->generator->create_user(array('username' => $studentusername3,
            'firstname' => $studentusername3, 'lastname' => $studentusername3));
        $this->generator->enrol_user($this->student1->id, $this->course->id, $studentrole->id);
        $this->generator->enrol_user($this->student2->id, $this->course->id, $studentrole->id);
        $teacherusername1 = 'moodleusertestteachert1_'.time();
        $teacherusername2 = 'moodleusertestteachert2_'.time();
        $teacherusername3 = 'moodleusertestteachert3_'.time();
        $this->teacher1 = $this->generator->create_user(array('username' => $teacherusername1,
            'firstname' => $teacherusername1, 'lastname' => $teacherusername1));
        $this->teacher2 = $this->generator->create_user(array('username' => $teacherusername2,
            'firstname' => $teacherusername2, 'lastname' => $teacherusername2));
        $this->teacher3 = $this->generator->create_user(array('username' => $teacherusername3,
            'firstname' => $teacherusername3, 'lastname' => $teacherusername3));
        $this->generator->enrol_user($this->teacher1->id, $this->course->id, $editingteacherrole->id);
        $this->generator->enrol_user($this->teacher2->id, $this->course->id, $editingteacherrole->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');

    }
    public function test_synchronize_group_members() {
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->create_group();
        $rocketchatid = $this->rocketchat->rocketchatid;
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->check_rocket_chat_group_members($rocketchatmembers);
        // Manually enrol teacher3  and student3 to Rocket.Chat.
        $this->rocketchatapimanager->enrol_user_to_group($rocketchatid, $this->student3);
        $this->rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $this->teacher3);
        // Remove student2 and teacher2 from Rocket.Chat.
        $this->rocketchatapimanager->unenrol_user_from_group($rocketchatid, $this->student2);
        $this->rocketchatapimanager->unenrol_user_from_group($rocketchatid, $this->teacher2);
        // Synchronize.
        mod_rocketchat_tools::synchronize_group_members($this->rocketchat);
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->check_rocket_chat_group_members($rocketchatmembers);
        // Play with moderator status in Rocket.Chat.
        $this->rocketchatapimanager->add_moderator_to_group($rocketchatid, $this->student1);
        $this->rocketchatapimanager->revoke_moderator_in_group($rocketchatid, $this->teacher1);
        // Synchronize.
        mod_rocketchat_tools::synchronize_group_members($this->rocketchat);
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->check_rocket_chat_group_members($rocketchatmembers);
    }

    public function test_synchronize_group_members_with_background_task() {
        set_config('background_add_instance', 1, 'mod_rocketchat');
        $this->create_group();
        // Need to trigger adhoc tasks to enrol.
        phpunit_util::run_all_adhoc_tasks();
        $rocketchatid = $this->rocketchat->rocketchatid;
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->check_rocket_chat_group_members($rocketchatmembers);
        // Manually enrol teacher3  and student3 to Rocket.Chat.
        $this->rocketchatapimanager->enrol_user_to_group($rocketchatid, $this->student3);
        $this->rocketchatapimanager->enrol_moderator_to_group($rocketchatid, $this->teacher3);
        // Remove student2 and teacher2 from Rocket.Chat.
        $this->rocketchatapimanager->unenrol_user_from_group($rocketchatid, $this->student2);
        $this->rocketchatapimanager->unenrol_user_from_group($rocketchatid, $this->teacher2);
        // Synchronize in backgroud.
        mod_rocketchat_tools::synchronize_group_members($this->rocketchat, true);
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->assertCount(5, $rocketchatmembers);
        $this->assertTrue(array_key_exists($this->student3->username, $rocketchatmembers));
        $this->assertTrue(array_key_exists($this->teacher3->username, $rocketchatmembers));
        $this->assertTrue($rocketchatmembers[$this->teacher3->username]->ismoderator);
        $this->assertFalse(array_key_exists($this->teacher2->username, $rocketchatmembers));
        $this->assertFalse(array_key_exists($this->student2->username, $rocketchatmembers));
        phpunit_util::run_all_adhoc_tasks();
        $rocketchatmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($rocketchatid);
        $this->check_rocket_chat_group_members($rocketchatmembers);
    }

    /**
     * @param $rocketchatmembers
     */
    protected function check_rocket_chat_group_members($rocketchatmembers): void {
        $this->assertCount(5, $rocketchatmembers);
        $this->assertTrue(array_key_exists($this->student1->username, $rocketchatmembers));
        $this->assertFalse($rocketchatmembers[$this->student1->username]->ismoderator);
        $this->assertTrue(array_key_exists($this->student2->username, $rocketchatmembers));
        $this->assertFalse($rocketchatmembers[$this->student2->username]->ismoderator);
        $this->assertTrue(array_key_exists($this->teacher1->username, $rocketchatmembers));
        $this->assertTrue($rocketchatmembers[$this->teacher1->username]->ismoderator);
        $this->assertTrue(array_key_exists($this->teacher2->username, $rocketchatmembers));
        $this->assertTrue($rocketchatmembers[$this->teacher2->username]->ismoderator);
    }

    private function create_group() {
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $this->generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
}
