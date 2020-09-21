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

class mod_rocketchat_api_manager_testcase extends advanced_testcase{
    private $rocketchatapimanager;
    public function test_initiate_connection() {
        $this->initiate_test_environment();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
        $this->assertNotNull($this->rocketchatapimanager->get_admin_user());
    }

    public function test_create_user_if_not_exists_and_delete() {
        $this->initiate_environment_and_connection();
        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_create_user_if_not_exists_two_time_and_delete() {
        $this->initiate_environment_and_connection();
        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        //try this second time : not created but retrieved
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_create_group() {
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);
        $group = $this->rocketchatapimanager->get_rocketchat_group_object($groupid, $groupname);
        $this->assertNotEmpty($group->info());
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
        $group = $this->rocketchatapimanager->get_rocketchat_group_object($groupid, $groupname);
        $this->assertEmpty($group->info());
    }

    public function test_enrol_unenrol_user_to_group() {
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);

        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleusermoderator = new stdClass();
        $moodleusermoderator->username = 'usertestMod'.time();
        $moodleusermoderator->firstname = 'moodleusertestModF';
        $moodleusermoderator->lastname = 'moodleusertestModL';
        $moodleusermoderator->email = $moodleusermoderator->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $rocketchatusermoderator = $this->rocketchatapimanager->create_user_if_not_exists($moodleusermoderator);
        $this->assertNotEmpty($rocketchatusermoderator);
        $this->assertTrue(property_exists($rocketchatusermoderator, '_id'));

        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));


        $this->assertNotEmpty($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $groupname,$moodleusermoderator));
        $this->assertNotEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $groupname,$moodleuser));

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(3, $members); // Adminuser included into group.

        $this->rocketchatapimanager->unenrol_moderator_from_group($groupid, $groupname, $moodleusermoderator);
        $this->rocketchatapimanager->unenrol_user_from_group($groupid, $groupname, $moodleuser);

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.

        $this->rocketchatapimanager->delete_rocketchat_group($groupid);

        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleusermoderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_enrol_unenrol_user_to_group_with_user_creation() {
        set_config('create_user_account_if_not_exists',1, 'mod_rocketchat');
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);

        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleusermoderator = new stdClass();
        $moodleusermoderator->username = 'usertestMod'.time();
        $moodleusermoderator->firstname = 'moodleusertestModF';
        $moodleusermoderator->lastname = 'moodleusertestModL';
        $moodleusermoderator->email = $moodleusermoderator->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $this->assertNotEmpty($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $groupname,$moodleusermoderator));
        $this->assertNotEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $groupname,$moodleuser));

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(3, $members); // Adminuser included into group.

        $this->rocketchatapimanager->unenrol_moderator_from_group($groupid, $groupname, $moodleusermoderator);
        $this->rocketchatapimanager->unenrol_user_from_group($groupid, $groupname, $moodleuser);

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.

        $this->rocketchatapimanager->delete_rocketchat_group($groupid);

        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleusermoderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_enrol_unenrol_user_to_group_with_user_creation_not_allowed() {
        set_config('create_user_account_if_not_exists',0, 'mod_rocketchat');
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);

        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleusermoderator = new stdClass();
        $moodleusermoderator->username = 'usertestMod'.time();
        $moodleusermoderator->firstname = 'moodleusertestModF';
        $moodleusermoderator->lastname = 'moodleusertestModL';
        $moodleusermoderator->email = $moodleusermoderator->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');

        $this->assertEmpty($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $groupname,$moodleusermoderator));
        $this->assertEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $groupname,$moodleuser));

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.
        $this->rocketchatapimanager->delete_rocketchat_group($groupid);
    }


    private function load_rocketchat_test_config(){
        global $CFG;
        require($CFG->dirroot.'/mod/rocketchat/config-test.php');
    }

    private function initiate_test_environment(): void {
        $this->resetAfterTest(true);
        $this->load_rocketchat_test_config();
    }

    private function initiate_environment_and_connection(){
        $this->initiate_test_environment();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
    }
}