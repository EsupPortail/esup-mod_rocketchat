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

class observer_testcase extends advanced_testcase{
    private $course;
    private $rocketchat;
    private $user;

    protected function setUp() {
        global $CFG, $DB;
        parent::setUp();
        set_config('recyclebin_patch',1,'mod_rocketchat');
        // Enable rocketchat module
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        // Disable recyclebin.
        set_config('coursebinenable', 0, 'tool_recyclebin');
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $username = 'moodleusertest'.time();
        $this->user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->user->id, $this->course->id, $student->id);
        //set a groupname for tests
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
    protected function tearDown()
    {
        ob_start();
        if(!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatmanager->delete_user($this->user->username);
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }

    public function test_user_delete() {
        // Structure created in setUp.
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatgroup = $rocketchatmanager->get_rocketchat_group_object($this->rocketchat->rocketchatid);
        $members = $rocketchatgroup->members();
        $this->assertCount(2, $members);
        delete_user($this->user);
        $rocketchatuser = $rocketchatmanager->get_rocketchat_user_object($this->user->username);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertNotEmpty($rocketchatuser->info());
        $rocketchatgroup = $rocketchatmanager->get_rocketchat_group_object($this->rocketchat->rocketchatid);
        $members = $rocketchatgroup->members();
        $this->assertCount(1, $members);
    }

    public function test_module_delete() {
        // Structure created in setUp.
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatgroup = $rocketchatmanager->get_rocketchat_group_object($this->rocketchat->rocketchatid);
        $members = $rocketchatgroup->members();
        $this->assertCount(2, $members);
        course_delete_module($this->rocketchat->cmid);
        $rocketchatuser = $rocketchatmanager->get_rocketchat_user_object($this->user->username);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertNotEmpty($rocketchatuser->info());
        $rocketchatgroup = $rocketchatmanager->get_rocketchat_group_object($this->rocketchat->rocketchatid);
        $this->assertNotEmpty($rocketchatgroup);
        $this->assertEmpty($rocketchatgroup->info());

    }
}