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

class retention_test extends advanced_testcase{

    private $userstudent1;
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
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('background_add_instance', 0, 'mod_rocketchat');
        $this->set_up_moodle_datas();
    }

    public function test_retention_add_update_instances() {
        set_config('retentionfeature', 1, 'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $generator = $this->getDataGenerator();
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname,
                'retentionenabled' => 1, 'maxage' => 20, 'filesonly' => 1, 'excludepinned' => 1));
        $rocketchatmanager = new rocket_chat_api_manager();
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertTrue(property_exists($groupinfos, 'retention'));
        $retention = $groupinfos->retention;
        $this->assertTrue(property_exists($retention, 'enabled'));
        $this->assertTrue($retention->enabled);
        $this->assertTrue(property_exists($retention, 'maxAge'));
        $this->assertEquals(20, $retention->maxAge);
        $this->assertTrue(property_exists($retention, 'filesOnly'));
        $this->assertTrue($retention->filesOnly);
        $this->assertTrue(property_exists($retention, 'excludePinned'));
        $this->assertTrue($retention->excludePinned);
        $this->rocketchat->retentionenabled = 0;
        rocketchat_update_instance($this->rocketchat);
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertTrue(property_exists($groupinfos, 'retention'));
        $retention = $groupinfos->retention;
        $this->assertTrue(property_exists($retention, 'enabled'));
        $this->assertFalse($retention->enabled);
        $this->assertTrue(property_exists($retention, 'maxAge'));
        $this->assertEquals(20, $retention->maxAge);
        $this->assertTrue(property_exists($retention, 'filesOnly'));
        $this->assertTrue($retention->filesOnly);
        $this->assertTrue(property_exists($retention, 'excludePinned'));
        $this->assertTrue($retention->excludePinned);
        rocketchat_update_instance($this->rocketchat);
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertTrue(property_exists($groupinfos, 'retention'));
        $retention = $groupinfos->retention;
        $this->assertTrue(property_exists($retention, 'enabled'));
        $this->assertFalse($retention->enabled);
        $this->assertTrue(property_exists($retention, 'maxAge'));
        $this->assertEquals(20, $retention->maxAge);
        $this->assertTrue(property_exists($retention, 'filesOnly'));
        $this->assertTrue($retention->filesOnly);
        $this->assertTrue(property_exists($retention, 'excludePinned'));
        $this->assertTrue($retention->excludePinned);
        $this->rocketchat->filesonly = 0;
        $this->rocketchat->excludepinned = 0;
        $this->rocketchat->maxage = 10;
        rocketchat_update_instance($this->rocketchat);
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertTrue(property_exists($groupinfos, 'retention'));
        $retention = $groupinfos->retention;
        $this->assertTrue(property_exists($retention, 'enabled'));
        $this->assertFalse($retention->enabled);
        $this->assertTrue(property_exists($retention, 'maxAge'));
        $this->assertEquals(10, $retention->maxAge);
        $this->assertTrue(property_exists($retention, 'filesOnly'));
        $this->assertFalse($retention->filesOnly);
        $this->assertTrue(property_exists($retention, 'excludePinned'));
        $this->assertFalse($retention->excludePinned);
        $this->rocketchat->maxage = 999;
        rocketchat_update_instance($this->rocketchat);
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertTrue(property_exists($groupinfos, 'retention'));
        $retention = $groupinfos->retention;
        $this->assertTrue(property_exists($retention, 'maxAge'));
        $this->assertEquals(999, $retention->maxAge);
    }

    public function test_retention_add_update_instances_without_retention() {
        set_config('retentionfeature', 0, 'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $generator = $this->getDataGenerator();
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname,
                'retentionenabled' => 1, 'overrideglobal' => 1, 'maxage' => 20, 'filesonly' => 1, 'excludepinned' => 1));
        $rocketchatmanager = new rocket_chat_api_manager();
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertFalse(property_exists($groupinfos, 'retention'));
        rocketchat_update_instance($this->rocketchat);
        $groupinfos = $rocketchatmanager->get_group_infos($this->rocketchat->rocketchatid);
        $groupinfos = $groupinfos->group;
        $this->assertFalse(property_exists($groupinfos, 'retention'));
    }

    protected function set_up_moodle_datas() {
        global $DB;
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $username = 'moodleusertest' . time();
        $this->userstudent1 = $generator->create_user(array('username' => $username, 'firstname' => $username,
            'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->userstudent1->id, $this->course->id, $student->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_' . time(),
            'mod_rocketchat');
    }
    public function tearDown() : void {
        ob_start();
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatmanager->delete_user($this->userstudent1->username);
        $rocketchatmanager->delete_rocketchat_group($this->rocketchat->rocketchatid);
        ob_get_contents();
        ob_end_clean();
        parent::tearDown();
    }
}
