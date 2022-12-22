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
 * mod_rocketchat backup restore tests.
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

class backup_restore_test extends advanced_testcase{
    private $course;
    private $rocketchat;
    private $newrocketchat;
    private $newrocketchatmodule;
    private $user;

    protected function setUp() : void {
        global $CFG, $DB;
        parent::setUp();
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('recyclebin_patch', 1, 'mod_rocketchat');
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        // Disable recyclebin.
        set_config('coursebinenable', 0, 'tool_recyclebin');
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $username = 'moodleusertest'.time();
        $this->user = $generator->create_user(array('username' => $username, 'firstname' => $username, 'lastname' => $username));
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($this->user->id, $this->course->id, $student->id);
        // Set a groupname for tests.
        set_config('groupnametoformat',
            'moodleunittest_{$a->courseshortname}_{$a->moduleid}_'.time(),
            'mod_rocketchat');
        $groupname = mod_rocketchat_tools::rocketchat_group_name(0, $this->course);
        $this->rocketchat = $generator->create_module('rocketchat',
            array('course' => $this->course->id, 'groupname' => $groupname));
    }
    protected function tearDown() : void {
        if (!empty($this->rocketchat)) {
            course_delete_module($this->rocketchat->cmid, true);
        }
        if (!empty($this->newrocketchat)) {
            ob_start();
            course_delete_module($this->newrocketchatmodule->id, true);
            ob_get_contents();
            ob_end_clean();
        }
        $rocketchatmanager = new rocket_chat_api_manager();
        $rocketchatmanager->delete_user($this->user->username);
        parent::tearDown();
    }

    public function test_backup_restore() {
        global $DB;
        // Backup course.
        set_config('background_restore', 0, 'mod_rocketchat');
        $newcourseid = $this->backup_and_restore($this->course);
        $modules = get_coursemodules_in_course('rocketchat', $newcourseid);
        $this->assertCount(1, $modules);
        $this->newrocketchatmodule = array_pop($modules);
        $this->newrocketchat = $DB->get_record('rocketchat', array('id' => $this->newrocketchatmodule->instance));
        $this->assertNotEquals($this->rocketchat->rocketchatid, $this->newrocketchat->rocketchatid);
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->newrocketchat->rocketchatid));
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
    }

    public function test_backup_restore_with_background_task() {
        global $DB;
        // Backup course.
        set_config('background_restore', 1, 'mod_rocketchat');
        $newcourseid = $this->backup_and_restore($this->course);
        $modules = get_coursemodules_in_course('rocketchat', $newcourseid);
        $this->assertCount(1, $modules);
        $this->newrocketchatmodule = array_pop($modules);
        $this->newrocketchat = $DB->get_record('rocketchat', array('id' => $this->newrocketchatmodule->instance));
        $this->assertNotEquals($this->rocketchat->rocketchatid, $this->newrocketchat->rocketchatid);
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->newrocketchat->rocketchatid));
        $this->assertCount(1, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
        ob_start();
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
    }

    public function test_duplicate_module() {
        global $DB;
        set_config('background_restore', 0, 'mod_rocketchat');
        $rocketchatmodule = get_coursemodule_from_id('rocketchat', $this->rocketchat->cmid, $this->course->id);
        $this->newrocketchatmodule = duplicate_module($this->course, $rocketchatmodule);
        $this->assertNotEmpty($this->newrocketchatmodule);
        $this->newrocketchat = $DB->get_record('rocketchat', array('id' => $this->newrocketchatmodule->instance));
        $this->assertNotEquals($this->rocketchat->rocketchatid, $this->newrocketchat->rocketchatid);
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->newrocketchat->rocketchatid));
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
    }

    public function test_duplicate_module_with_background_task() {
        global $DB;
        set_config('background_restore', 1, 'mod_rocketchat');
        $rocketchatmodule = get_coursemodule_from_id('rocketchat', $this->rocketchat->cmid, $this->course->id);
        $this->newrocketchatmodule = duplicate_module($this->course, $rocketchatmodule);
        $this->assertNotEmpty($this->newrocketchatmodule);
        $this->newrocketchat = $DB->get_record('rocketchat', array('id' => $this->newrocketchatmodule->instance));
        $this->assertNotEquals($this->rocketchat->rocketchatid, $this->newrocketchat->rocketchatid);
        $rocketchatmanager = new rocket_chat_api_manager();
        $this->assertTrue($rocketchatmanager->group_exists($this->newrocketchat->rocketchatid));
        $this->assertCount(1, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
        ob_start();
        phpunit_util::run_all_adhoc_tasks();
        ob_get_contents();
        ob_end_clean();
        $this->assertCount(2, $rocketchatmanager->get_group_members($this->newrocketchat->rocketchatid));
    }

    protected function backup_and_restore($course) {
        global $USER, $CFG;
        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;
        // Do backup with default settings.
        set_config('backup_general_users', 1, 'backup');
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id,
            backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL,
            $USER->id);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
            $course->fullname, $course->shortname . '_2', $course->category);
        $rc = new restore_controller('test-restore-course', $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
            backup::TARGET_NEW_COURSE);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
