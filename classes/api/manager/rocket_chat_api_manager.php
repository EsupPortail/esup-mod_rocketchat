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
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rocketchat\api\manager;

use Horde\Socket\Client\Exception;
use RocketChat\RocketChatException;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');

class rocket_chat_api_manager{
    private $rocketchatapiconfig;
    private $adminuser;
    private $verbose;

    public function get_instance_url() {
        return $this->rocketchatapiconfig->get_instanceurl();
    }
    public function get_admin_user() {
        return $this->adminuser;
    }
    public function is_verbose() {
        return $this->verbose;
    }
    public function __construct($user=null, $token=null) {
        $this->rocketchatapiconfig = new rocket_chat_api_config();
        $this->initiate_connection($user, $token);
    }

    private function initiate_connection($user = null, $token = null) {
        // User amanager object , logged to remote Rocket.Chat.
        $this->adminuser = new \RocketChat\UserManager(
            1,
            is_null($user) ? $this->rocketchatapiconfig->get_apiuser() : $user,
            is_null($user) ? $this->rocketchatapiconfig->get_api_token() : $token,
            $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot()
        );
    }

    public function login_admin() {
        $this->adminuser->login_token($this->rocketchatapiconfig->get_api_token());
    }

    public function close_connection() {
        $this->adminuser->logout();
    }
    public function get_rocketchat_channel_object($channelid, $channelname='') {
        $channel = new \stdClass();
        $channel->_id = $channelid;
        $channel->name = $channelname;
        return new \RocketChat\Channel($channel, array(),  $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_role_object() {
        return new \RocketChat\Role(array(),  $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }
    public function get_rocketchat_room_object($roomid,  $roomname='') {
        $room = new \stdClass();
        $room->_id = $roomid;
        $room->name = $roomname;
        return new \RocketChat\Room($room, $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_group_object($groupid, $groupname='') {
        $group = new \stdClass();
        $group->_id = $groupid;
        $group->name = $groupname;
        return new \RocketChat\Group($group, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_user_with_id_object($userid) {
        $fields = array();
        $fields['_id'] = $userid;
        return new \RocketChat\User(null, null , $fields,
            $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
    }

    /**
     * @param $username Rocket.Chat username
     * @param string $password
     * @param array $fields
     * @return \RocketChat\User
     */
    public function get_rocketchat_user_object($username, $password='', $fields = array()) {
        return new \RocketChat\User($username, $password , $fields, $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_client_object() {
        return new \RocketChat\Client($this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function create_rocketchat_group($name) {
        $group = new \RocketChat\Group($name, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        // Check that group is not already exists.
        try {
            $groupexists = $group->isGroupAlreadyExists();
            if (!$groupexists) {
                $group->create();
            } else {
                // Change group name.
                return $this->create_rocketchat_group($name.'_'.time());
            }
            return $group->id;
        } catch (RocketChatException $e) {
            self::moodle_debugging_message('', $e, DEBUG_ALL);
            if (!PHPUNIT_TEST) {
                throw new moodle_exception(get_string('groupecreationerror', 'mod_rocketchat'));
            }
        }
    }

    public function delete_rocketchat_group($id, $groupname='') {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = $groupname;
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        if ($group) {
            try {
                return $group->delete();
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("Error while deleting Rocket.Chat remote group $group->id", $e, DEBUG_ALL);
            }
        }
        return false;
    }

    public function save_rocketchat_group_settings($groupid, $settings) {
        $identifier = new \stdClass();
        $identifier->_id = $groupid;
        $identifier->name = '';
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        // Format settings.
        $rcsettings1 = array();
        $rcsettings2 = array();
        foreach ($settings as $settingname => $settingvalue) {
            switch($settingname) {
                case 'retentionenabled':
                    $rcsettings1['retentionEnabled'] = (boolean) $settingvalue;
                    // Set overrideglobal to retentionEnabled value to simplify.
                    $rcsettings1['retentionOverrideGlobal'] = (boolean) $settingvalue;
                    break;
                case 'maxage' :
                    $rcsettings2['retentionMaxAge'] = $settingvalue;
                    break;
                case 'maxage_limit' :
                    $rcsettings2['retentionMaxAgeLimit'] = $settingvalue;
                    break;
                case 'filesonly' :
                    $rcsettings2['retentionFilesOnly'] = (boolean) $settingvalue;
                    break;
                case 'excludepinned' :
                    $rcsettings2['retentionExcludePinned'] = (boolean) $settingvalue;
                    break;
                default:
                    break;
            }
        }
        try {
            // Need to make it in two times because of Rocket.Chat behaviour.
            if (count($rcsettings1) > 0) {
                $group->saveRoomSettings($rcsettings1);
            }
            if (count($rcsettings2) > 0) {
                $group->saveRoomSettings($rcsettings2);
            }

        } catch (RocketChatException $e) {
            self::moodle_debugging_message("Error while save settings into Room $group->id", $e, DEBUG_ALL);
        }
    }

    public function archive_rocketchat_group($id) {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = '';
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        try {
            return $group->archive();
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("Error while archiving remote group $group->id", $e, DEBUG_ALL);
        }
        return false;
    }

    public function unarchive_rocketchat_group($id) {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = '';
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        try {
            return $group->unarchive();
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("Error while archiving remote group $group->id", $e, DEBUG_ALL);
        }
        return false;
    }

    public function enrol_user_to_group($groupid, $moodleuser, &$user=null) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $createusermode = get_config('mod_rocketchat', 'create_user_account_if_not_exists');
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = false;
        if ($createusermode) {
            try {
                $user = $this->create_user_if_not_exists($moodleuser);
            } catch (RocketChatException $e) {
                self::moodle_debugging_message(
                    "User $rocketchatusername not exists in Rocket.Chat and was not succesfully created.",
                    $e);
            }
        } else {
            try {
                $user = $group->user_info($identifier);
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("User $rocketchatusername not exists in Rocket.Chat", $e);
            }
        }
        if (!$user) {
            return false;
        }
        try {
            $return = $group->invite($user->_id);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not added as user to remote Rocket.Chat group", $e);
        }
        return $return ? $group : false;
    }

    public function add_moderator_to_group($groupid, $moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        try {
            $user = $this->get_user_infos($rocketchatusername);
            $group = $this->get_rocketchat_group_object($groupid);
            return $group->addModerator($user->_id);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not added as moderator to remote Rocket.Chat group",
                $e);
        }
        return false;
    }

    public function enrol_moderator_to_group($groupid, $moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        $user = null;
        $group = false;
        try {
            $group = $this->enrol_user_to_group($groupid, $moodleuser, $user);
        } catch ( RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername was not enrolled to remote Rocket.Chat group"
                , $e);
        }
        if ($group) {
            try {
                return $group->addModerator($user->_id);
            } catch (RocketChatException $e) {
                self::moodle_debugging_message(
                    "User $rocketchatusername not added as moderator to remote Rocket.Chat group"
                    , $e);
            }
        }
        return false;
    }

    public function unenrol_user_from_group($groupid, $moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = false;
        try {
            $user = $group->user_info($identifier);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not exists in Rocket.Chat", $e);
            return false;
        }
        $return = false;
        try {
            $return = $group->kick($user->_id);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not removed as user from remote Rocket.Chat group", $e);
        }
        return $return ? $group : false;
    }

    public function revoke_moderator_in_group($groupid, $moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = false;
        try {
            $user = $group->user_info($identifier);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not exists in Rocket.Chat", $e);
            return false;
        }
        try {
            return $group->removeModerator($user->_id);
        } catch ( RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not removed as moderator from remote Rocket.Chat group",
                $e);
        }
        return false;
    }

    public function unenrol_moderator_from_group($groupid, $moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $identifier = new \stdClass();
        $identifier->username = $rocketchatusername;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = false;
        try {
            $user = $group->user_info($identifier);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not exists in Rocket.Chat", $e);
            return false;
        }
        $return = false;
        try {
            $return = $group->removeModerator($user->_id);
        } catch ( RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not removed as moderator from remote Rocket.Chat group",
                $e);
        }
        $return2 = false;
        try {
            $this->unenrol_user_from_group($groupid, $moodleuser);
        } catch ( RocketChatException $e) {
            self::moodle_debugging_message("User $rocketchatusername not unenrolled from remote Rocket.Chat group",
                $e);
        }
        return $return && $return2;
    }

    public function create_user_if_not_exists($moodleuser) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleuser->username);
        $rocketchatuserinfos = new \stdClass();
        $rocketchatuserinfos->nickname = get_string('rocketchat_nickname', 'mod_rocketchat', $moodleuser);
        $rocketchatuserinfos->email = $moodleuser->email;
        $rocketchatuserinfos->username = $rocketchatusername;
        $rocketchatuserinfos->password = generate_password();
        // Add options if necessary
        $rocketchatconfig = get_config('mod_rocketchat');
        $auths = explode(',', $rocketchatconfig->user_creation_adv_options_auth_methods);
        if (in_array($moodleuser->auth, $auths)) {
            if ($rocketchatconfig->user_creation_adv_options_requirePasswordChange) {
                $rocketchatuserinfos->requirePasswordChange = "true";
            }
            if ($rocketchatconfig->user_creation_adv_options_setRandomPassword) {
                $rocketchatuserinfos->setRandomPassword = "true";
            }
            if ($rocketchatconfig->user_creation_adv_options_sendWelcomeEmail) {
                $rocketchatuserinfos->sendWelcomeEmail = "true";
            }
            if ($rocketchatconfig->user_creation_adv_options_verify) {
                $rocketchatuserinfos->verified = "false";
            }
        }
        $user = $this->adminuser->create($rocketchatuserinfos);
        if (PHPUNIT_TEST) {
            $user->password = $rocketchatuserinfos->password;
        }
        return $user;
    }

    public function get_group_members($groupid, $groupname = '') {
        $group = $this->get_rocketchat_group_object($groupid, $groupname);
        if ($group) {
            $members = false;
            try {
                $members = $group->members();
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("Error while retrieving group members", $e);
            }
            if (!$members) {
                return array();
            } else {
                return $members;
            }
        }
        return array();
    }

    public function get_enriched_group_members($groupid, $groupname = '') {
        $group = $this->get_rocketchat_group_object($groupid, $groupname);
        $enrichedmembers = array();
        if ($group) {
            $members = false;
            try {
                $members = $group->members();
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("Error while retrieving group members", $e);
            }
            if (!$members) {
                return array();
            } else {
                foreach ($members as $member) {
                    $enrichedmembers[$member->username] = $member;
                }
                return $enrichedmembers;
            }
        }
        return array();
    }

    public function get_enriched_group_members_with_moderators($groupid, $groupname = '') {
        $group = $this->get_rocketchat_group_object($groupid, $groupname);
        $enrichedmembers = array();
        if ($group) {
            $members = false;
            try {
                $members = $group->members();
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("Error while retrieving group members", $e);
            }
            if (!$members) {
                return array();
            } else {
                foreach ($members as $member) {
                    $member->ismoderator = false;
                    $enrichedmembers[$member->username] = $member;
                }
                $moderators = $this->get_group_moderators($groupid);
                foreach ($moderators as $moderator) {
                    $enrichedmembers[$moderator->username]->ismoderator = true;
                }
                return $enrichedmembers;
            }
        }
        return array();
    }

    public function delete_user($moodleusername) {
        $rocketchatusername = \mod_rocketchat_tools::rocketchat_username($moodleusername);
        $rocketuser = new \stdClass();
        $rocketuser->username = $rocketchatusername;
        try {
            $rocketuser = $this->adminuser->info($rocketuser);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("user $rocketchatusername not found in Rocket.Chat while attempt to delete", $e);
        }
        if (!$rocketuser || !isset($rocketuser->user->_id)) {
            return false;
        }
        try {
            return $this->adminuser->delete($rocketuser->user->_id);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("user $rocketuser->user not deleted", $e);
        }
    }

    public function unenroll_all_users_from_group($groupid) {
        $group = $$this->get_rocketchat_group_object($groupid);
        try {
            $group->cleanHistory();
            $members = $group->members();
            foreach ($members as $member) {
                $user = $this->get_rocketchat_user_object($member->username);
                $group->kick($user->_id);
            }
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("unenroll_all_users_from_group error", $e);
        }
    }

    public function clean_history($roomid) {
        $group = $this->get_rocketchat_group_object($roomid);
        $group->cleanHistory();
    }

    public function get_groupname($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            try {
                $groupinfo = $group->info();
                return $groupinfo->group->name;
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("groupname error", $e);
            }
        }
        return null;
    }

    public function get_group_infos($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            try {
                $groupinfo = $group->info();
                if (!empty($groupinfo)) {
                    return $groupinfo;
                }
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("group info error", $e);
            }
        }
        return false;
    }

    public function group_exists($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            try {
                $groupinfo = $group->info();
                if (!empty($groupinfo)) {
                    return true;
                }
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("group_exists", $e);
            }
        }
        return false;
    }

    /**
     * @param $username Rocket.Chat username
     * @return mixed
     * @throws RocketChatException
     */
    public function get_user_infos($username) {
        $identifier = new \stdClass();
        $identifier->username = $username;
        $client = $this->get_rocketchat_client_object();
        return $client->user_info($identifier);
    }

    public function kick_all_group_members($groupid) {
        $rocketchatapiuser = get_config('mod_rocketchat', 'apiuser');
        $group = $this->get_rocketchat_group_object($groupid);
        $members = false;
        try {
            $members = $group->members();
        } catch (RocketChatException $e) {
            self::moodle_debugging_message("error while retrieving group members", $e);
        }
        foreach ($members as $member) {
            if ($member->_id != $rocketchatapiuser) {
                try {
                    $rocketchatuser = $this->get_user_infos($member->username);
                    $group->kick($rocketchatuser->_id);
                } catch (RocketChatException $e) {
                    self::moodle_debugging_message(
                        "Rockat.Chat API error : user $member->username not kicked from Rocket.Chat group $groupid",
                        $e);

                }
            }
        }
    }

    public function post_message($roomid, $message) {
        $channel = $this->get_rocketchat_channel_object($roomid);
        try {
            $channel->postMessage($message);
        } catch (RocketChatException $e) {
            self::moodle_debugging_message('', $e);
        }
    }

    public function get_group_messages($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        try {
            return $group->getMessages();
        } catch (RocketChatException $e) {
            self::moodle_debugging_message('', $e);
        }
        return array();
    }

    /**
     * @param $username Rocket.Chat username
     * @return bool
     */
    public function user_exists($username) {
        try {
            $user = $this->get_rocketchat_user_object($username);
            if (!empty($user) && !empty($user->info())) {
                return true;
            }
        } catch (RocketChatException $e) {
            return false;
        }
        return false;
    }

    public function group_archived($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if (!empty($group)) {
            try {
                $groupinfo = $group->info()->group;
                if (!empty($groupinfo)) {
                    if (property_exists($groupinfo, 'archived')) {
                        return $groupinfo->archived;
                    }
                }
            } catch (RocketChatException $e) {
                self::moodle_debugging_message("Rocket.Chat API Rest error group $groupid not found", $e);
            }
        }
        return false;
    }

    public function get_group_moderators($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            try {
                $moderators = $group->moderators();
                if (!$moderators) {
                    return array();
                } else {
                    return $moderators;
                }
            } catch (RocketChatException $e) {
                self::moodle_debugging_message('', $e);
            }
        }
        return array();
    }
    public function get_adminuser_info() {
        return $this->adminuser->me();
    }

    /**
     * @param $moodleuser
     * @param $e
     */
    public static function moodle_debugging_message($message, $e, $level = DEBUG_DEVELOPER) {
        if (!empty($message)) {
            debugging($message."\n"."Rocket.chat api Error ".$e->getCode()." : ".$e->getMessage(), $level);
        } else {
            debugging("Rocket.chat api Error ".$e->getCode()." : ".$e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    /**
     * @return \RocketChat\Channel
     */
    public function get_rocketchat_channel_with_id_object() {
        return new \RocketChat\Channel(null, null,
            $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
    }
}
