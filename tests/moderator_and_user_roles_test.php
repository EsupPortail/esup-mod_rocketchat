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

class moderator_and_user_roles_test extends advanced_testcase {
    private $rocketchatapimanager;
    private $course;
    private $rocketchat;
    private $studentrole;
    private $guestrole;
    private $editingteacherrole;
    private $teacherrole;
    private $student1;
    private $teacher1;
    private $generator;


    public function test_set_empty_roles() {
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $this->generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname, 'moderatorroles' => '', 'userroles' => ''));
        $this->assertCount(1, $this->rocketchatapimanager->get_enriched_group_members($this->rocketchat->rocketchatid));
        $this->assertFalse($this->rocketchatapimanager->user_exists($this->student1->username));
        $this->assertFalse($this->rocketchatapimanager->user_exists($this->teacher1->username));
    }

    public function test_change_roles() {
        global $DB;
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $this->generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname,
                'moderatorroles' => $this->editingteacherrole->id, 'userroles' => $this->studentrole->id));
        $members = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($this->rocketchat->rocketchatid);
        $this->assertCount(3, $members);
        $this->assertTrue(array_key_exists($this->student1->username, $members));
        $this->assertFalse($members[$this->student1->username]->ismoderator);
        $this->assertTrue(array_key_exists($this->teacher1->username, $members));
        $this->assertTrue($members[$this->teacher1->username]->ismoderator);
        $coursemodule = $DB->get_record('course_modules', array('instance' => $this->rocketchat->id));
        list($cm, , , $data, ) = get_moduleinfo_data($coursemodule, $this->course);
        $cm->modname = 'rocketchat';
        $data->moderatorroles = $this->teacherrole->id;
        $data->userroles = $this->guestrole->id;
        $mform = new simpleform();
        $mform->set_data($data);
        update_moduleinfo($cm, $data, $this->course, $mform);
        $members = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($this->rocketchat->rocketchatid);
        $this->assertCount(1, $members);
        $this->assertFalse(array_key_exists($this->student1->username, $members));
        $this->assertFalse(array_key_exists($this->teacher1->username, $members));
    }

    public function setUp() : void {
        global $DB;
        parent::setUp();
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        $this->initiate_test_environment();
    }

    public function tearDown() : void {
        ob_start();
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        if ($this->rocketchatapimanager->user_exists($this->student1->username)) {
            $this->rocketchatapimanager->delete_user($this->student1->username);
        }
        if ($this->rocketchatapimanager->user_exists($this->teacher1->username)) {
            $this->rocketchatapimanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        }
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }

    private function load_rocketchat_test_config() {
        global $CFG;
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
    }

    private function initiate_test_environment() {
        global $DB;
        set_config('background_add_instance', 0, 'mod_rocketchat');
        set_config('background_restore', 0, 'mod_rocketchat');
        set_config('background_synchronize', 0, 'mod_rocketchat');
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $this->resetAfterTest(true);
        $this->load_rocketchat_test_config();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
        $this->setAdminUser();
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->guestrole = $DB->get_record('role', array('shortname' => 'guest'));
        $this->editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $studentusername1 = 'moodleuserteststudent1_'.time();
        $this->student1 = $this->generator->create_user(array('username' => $studentusername1,
            'firstname' => $studentusername1, 'lastname' => $studentusername1));
        $this->generator->enrol_user($this->student1->id, $this->course->id, $this->studentrole->id);
        $teacherusername1 = 'moodleusertestteachert1_'.time();
        $this->teacher1 = $this->generator->create_user(array('username' => $teacherusername1,
            'firstname' => $teacherusername1, 'lastname' => $teacherusername1));
        $this->generator->enrol_user($this->teacher1->id, $this->course->id, $this->editingteacherrole->id);
    }

}
class simpleform extends moodleform{
    protected function definition() {
        // TODO: Implement definition() method.
    }
}

