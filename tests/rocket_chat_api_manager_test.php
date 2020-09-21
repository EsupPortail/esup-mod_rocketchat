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
 * local_digital_training_account_services tests.
 *
 * @package    local_digital_training_account_services
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
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