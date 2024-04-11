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

class rocketchat_api_manager_test extends advanced_testcase{
    private $rocketchatapimanager;

    public function setUp() : void {
        global $DB;
        parent::setUp();
        set_config('background_enrolment_task', '', 'mod_rocketchat');
        set_config('background_add_instance', 0, 'mod_rocketchat');
        // Enable rocketchat module.
        $modulerecord = $DB->get_record('modules', ['name' => 'rocketchat']);
        $modulerecord->visible = 1;
        $DB->update_record('modules', $modulerecord);
        $this->initiate_test_environment();
        set_config('create_user_account_if_not_exists', 1, 'mod_rocketchat');
    }

    public function test_initiate_connection() {
        $this->initiate_test_environment();
        $this->rocketchatapimanager = new \mod_rocketchat\api\manager\rocket_chat_api_manager();
        $this->assertNotNull($this->rocketchatapimanager->get_admin_user());
    }

    public function test_get_enriched_group_members_with_moderators() {
        $this->initiate_environment_and_connection();
        list($moderator, $rocketchatmoderator, $user, $rocketchatuser, $groupid) = $this->initiate_group_with_user();
        $enrichedmembers = $this->rocketchatapimanager->get_enriched_group_members_with_moderators($groupid);
        $this->assertCount(3, $enrichedmembers); // 2 + owner.
        $this->assertTrue(array_key_exists($rocketchatmoderator->username, $enrichedmembers));
        $this->assertTrue(array_key_exists($rocketchatuser->username, $enrichedmembers));
        $this->assertTrue($enrichedmembers[$rocketchatmoderator->username]->ismoderator);
        $this->assertFalse($enrichedmembers[$rocketchatuser->username]->ismoderator);
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
        $this->assertFalse($this->rocketchatapimanager->group_exists($groupid));
        $this->assertDebuggingCalled();
        $this->assertTrue($this->rocketchatapimanager->delete_user($moderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($user->username));
    }

    public function test_get_enriched_group_members() {
        $this->initiate_environment_and_connection();
        list($moderator, $rocketchatmoderator, $user, $rocketchatuser, $groupid) = $this->initiate_group_with_user();
        $enrichedmembers = $this->rocketchatapimanager->get_enriched_group_members($groupid);
        $this->assertCount(3, $enrichedmembers); // 2 + owner.
        $this->assertTrue(array_key_exists($rocketchatmoderator->username, $enrichedmembers));
        $this->assertTrue(array_key_exists($rocketchatuser->username, $enrichedmembers));
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
        $this->assertFalse($this->rocketchatapimanager->group_exists($groupid));
        $this->assertDebuggingCalled();
        $this->assertTrue($this->rocketchatapimanager->delete_user($moderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($user->username));
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
        // Try this second time : not created but retrieved.
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_create_user_if_not_exists_and_delete_with_options() {
        $this->initiate_environment_and_connection();
        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $moodleuser->auth = 'manual';
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');
        set_config('user_creation_adv_options_auth_methods','manual','mod_rocketchat');
        set_config('user_creation_adv_options_requirePasswordChange',1,'mod_rocketchat');
        set_config('user_creation_adv_options_setRandomPassword',1,'mod_rocketchat');
        set_config('user_creation_adv_options_sendWelcomeEmail',1,'mod_rocketchat');
        set_config('user_creation_adv_options_verify',1,'mod_rocketchat');
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $this->assertTrue($this->rocketchatapimanager->user_exists($moodleuser->username));
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatuser, '_id'));
        $this->assertTrue(property_exists($rocketchatuser, 'requirePasswordChange'));
        $this->assertTrue($rocketchatuser->requirePasswordChange);
        $this->assertFalse($rocketchatuser->emails[0]->verified);
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_create_group() {
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);
        $this->assertTrue($this->rocketchatapimanager->group_exists($groupid));
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
        $this->assertFalse($this->rocketchatapimanager->group_exists($groupid));
        $this->assertDebuggingCalled();
    }

    public function test_create_group_invalid_groupname() {
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup/'.time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertDebuggingCalled();
        $this->assertEmpty($groupid);
        $sanitizedgroupname = mod_rocketchat_tools::sanitize_groupname($groupname);
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($sanitizedgroupname);
        $this->assertNotEmpty($groupid);
        $this->assertTrue($this->rocketchatapimanager->group_exists($groupid));
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
        $this->assertFalse($this->rocketchatapimanager->group_exists($groupid));
        $this->assertDebuggingCalled();
    }

    public function test_create_group_groupname_with_whitespace() {
        $this->initiate_environment_and_connection();
        $groupname = 'moodletestgroup '.time();
        $sanitizedgroupname = mod_rocketchat_tools::sanitize_groupname($groupname);
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($sanitizedgroupname);
        $this->assertNotEmpty($groupid);
        $this->assertTrue($this->rocketchatapimanager->group_exists($groupid));
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid));
    }

    public function test_create_groups_with_same_names() {
        $this->initiate_environment_and_connection();
        $groupname1 = 'moodletestgroup'.time();
        $groupid1 = $this->rocketchatapimanager->create_rocketchat_group($groupname1);
        $this->assertNotEmpty($groupid1);
        $info1 = $this->rocketchatapimanager->get_group_infos($groupid1);
        $this->assertNotEmpty($info1);
        // Create same second time.
        $groupid2 = $this->rocketchatapimanager->create_rocketchat_group($groupname1);
        $this->assertNotEmpty($groupid2);
        $info2 = $this->rocketchatapimanager->get_group_infos($groupid2);
        $this->assertNotEmpty($info2);
        $groupname2 = $info2->group->name;
        $this->assertNotEquals($groupname1, $groupname2);
        $this->assertTrue(strpos($groupname2, $groupname1) == 0);
        // Clean.
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid1));
        $this->assertTrue($this->rocketchatapimanager->delete_rocketchat_group($groupid2));
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

        $this->assertNotEmpty($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $moodleusermoderator));
        $this->assertNotEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $moodleuser));

        $members = $this->rocketchatapimanager->get_group_members($groupid);
        $this->assertTrue(is_array($members));
        $this->assertCount(3, $members); // Adminuser included into group.

        $this->rocketchatapimanager->unenrol_moderator_from_group($groupid, $moodleusermoderator);
        $this->rocketchatapimanager->unenrol_user_from_group($groupid, $moodleuser);

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.

        $this->rocketchatapimanager->delete_rocketchat_group($groupid);

        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleusermoderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_enrol_unenrol_user_to_group_with_user_creation() {
        set_config('create_user_account_if_not_exists', 1, 'mod_rocketchat');
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

        $this->assertNotEmpty($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $moodleusermoderator));
        $this->assertNotEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $moodleuser));

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(3, $members); // Adminuser included into group.

        $this->rocketchatapimanager->unenrol_moderator_from_group($groupid, $moodleusermoderator);
        $this->rocketchatapimanager->unenrol_user_from_group($groupid, $moodleuser);

        $members = $this->rocketchatapimanager->get_group_members($groupid, $groupname);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.

        $this->rocketchatapimanager->delete_rocketchat_group($groupid);

        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleusermoderator->username));
        $this->assertTrue($this->rocketchatapimanager->delete_user($moodleuser->username));
    }

    public function test_enrol_unenrol_user_to_group_with_user_creation_not_allowed() {
        set_config('create_user_account_if_not_exists', 0, 'mod_rocketchat');
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
        // Creation Not allowed.
        $this->assertFalse($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $moodleusermoderator));
        $this->assertDebuggingCalled();
        $this->assertFalse($this->rocketchatapimanager->enrol_user_to_group($groupid, $moodleuser));
        $this->assertDebuggingCalled();
        $members = $this->rocketchatapimanager->get_group_members($groupid);
        $this->assertTrue(is_array($members));
        $this->assertCount(1, $members); // Adminuser included into group.
        $this->rocketchatapimanager->delete_rocketchat_group($groupid);
        // No need to delete users since they were not created.
    }

    public function test_delete_all_group_messages() {
        $this->initiate_environment_and_connection();
        set_config('create_user_account_if_not_exists', 1, 'mod_rocketchat');
        $this->rocketchatapimanager->login_admin();
        $groupname = 'moodletestgroup' . time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);

        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moodleuser = new stdClass();
        $moodleuser->username = 'usertest'.time();
        $moodleuser->firstname = 'moodleusertestF';
        $moodleuser->lastname = 'moodleusertestL';
        $moodleuser->email = $moodleuser->username.'@'.(!empty($domainmail) ? $domainmail : 'moodle.test');
        $user = $this->rocketchatapimanager->create_user_if_not_exists($moodleuser);
        $messages = $this->rocketchatapimanager->get_group_messages($groupid);
        $this->assertCount(0, $messages);
        $moodleuser->password = $user->password; // Password only returned in PHPUNIT_TEST mode.
        $this->rocketchatapimanager->enrol_user_to_group($groupid, $moodleuser);
        $messages = $this->rocketchatapimanager->get_group_messages($groupid);
        $this->assertCount(1, $messages); // User enrolments generate a message.
        $this->rocketchatapimanager->post_message($groupid, 'a message');
        $messages = $this->rocketchatapimanager->get_group_messages($groupid);
        $this->assertCount(2, $messages);
        $this->rocketchatapimanager->clean_history($groupid);
        $messages = $this->rocketchatapimanager->get_group_messages($groupid);
        $this->assertCount(0, $messages);
        $this->rocketchatapimanager->delete_rocketchat_group($groupid);
        $this->rocketchatapimanager->delete_user($moodleuser->username);
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

    /**
     * @return array
     * @throws dml_exception
     */
    protected function initiate_group_with_user() {
        $domainmail = get_config('mod_rocketchat', 'domainmail');
        $moderator = new stdClass();
        $moderator->username = 'moodleusertestModerator' . time();
        $moderator->firstname = 'moodleusertestModerator';
        $moderator->lastname = $moderator->firstname;
        $moderator->email = $moderator->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $rocketchatmoderator = $this->rocketchatapimanager->create_user_if_not_exists($moderator);
        $user = new stdClass();
        $user->username = 'moodleusertestUser' . time();
        $user->firstname = 'moodleusertestUser';
        $user->lastname = $user->firstname;
        $user->email = $user->username . '@' . (!empty($domainmail) ? $domainmail : 'moodle.test');
        $rocketchatuser = $this->rocketchatapimanager->create_user_if_not_exists($user);
        $this->assertNotEmpty($rocketchatmoderator);
        $this->assertNotEmpty($rocketchatuser);
        $this->assertTrue(property_exists($rocketchatmoderator, '_id'));
        $groupname = 'moodletestgroup' . time();
        $groupid = $this->rocketchatapimanager->create_rocketchat_group($groupname);
        $this->assertNotEmpty($groupid);
        $this->assertTrue($this->rocketchatapimanager->group_exists($groupid));
        $this->waitForSecond(); // Some times seems that Rocket.Chat server is too long.
        $this->assertTrue($this->rocketchatapimanager->enrol_moderator_to_group($groupid, $moderator));
        $this->assertNotEmpty($this->rocketchatapimanager->enrol_user_to_group($groupid, $user));
        return array($moderator, $rocketchatmoderator, $user, $rocketchatuser, $groupid);
    }
}
