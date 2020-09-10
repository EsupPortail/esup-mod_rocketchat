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
 * rocket chat api config class
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rocketchat\api\manager;


global $CFG;
require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');

class rocket_chat_api_manager{
    private $rocketchatapiconfig;

    public function __construct() {
        $this->rocketchatapiconfig = new rocket_chat_api_config();
        $this->initiate_connection();
    }

    private function initiate_connection() {
        $adminuser = new \RocketChat\User($this->rocketchatapiconfig->get_apiuser(), $this->rocketchatapiconfig->get_apipassword(),
            array(), $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
        // Log in with save option in order to add id and token to header
        if (!$adminuser->login(true)) {
            print_error('Rocket.Chat admin not logged in');
        }

    }

    public function close_connection() {
        $adminuser = new \RocketChat\User($this->rocketchatapiconfig->get_apiuser(), $this->rocketchatapiconfig->get_apipassword(),
            $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
        // Log in with save option in order to add id and token to header
        if (!$adminuser->logout()) {
            debugging('Rocket.Chat admin not logged in', DEBUG_MINIMAL);
        }

    }

    public function get_rocketchat_client_object($groupid){
        return new \RocketChat\Client($this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_group_object($groupid){
        $group = new \stdClass();
        $group->id = $groupid;
        return new \RocketChat\Group($group, array(),$this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_user_with_id_object($userid){
        $fields = array();
        $fields['_id'] = $userid;
        return new \RocketChat\User(null, null , $fields, $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_user_object($username, $password, $fields = array()){
        return new \RocketChat\User($username, $password , $fields, $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function create_rocketchat_group($name){
        $group = new \RocketChat\Group($name, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        $group->create();
        return $group->id;
    }

    public function __destruct() {
        //$this->close_connection();
    }
}