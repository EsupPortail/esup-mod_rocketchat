<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class Group extends Client {

    public $id;
    public $name;
    public $members = array();
    public $archived = false;
    public $readonly = true;
    public $announcement = "";

    public function __construct($name, $members = array(), $options = array(), $instanceurl = null, $restroot = null){
        if(!is_null($instanceurl) && !is_null($restroot)){
            parent::__construct($instanceurl, $restroot);
        }else {
            parent::__construct();
        }
        if( is_string($name) ) {
            $this->name = $name;
        } else if( isset($name->_id) ) {
            $this->name = $name->name;
            $this->id = $name->_id;
        }
        if( isset($options['readonly'])){
            $this->readonly = (bool) $options['readonly'];
        }
        if( isset($options['archived'])){
            $this->archived = (bool) $options['archived'];
        }
        foreach($members as $member){
            if( is_a($member, '\RocketChat\User') ) {
                $this->members[] = $member;
            } else if( is_string($member) ) {
                // TODO
                $this->members[] = new User($member);
            }
        }
    }

    /**
     * Creates a new private group.
     */
    public function create(){
        // get user ids for members
        $members_id = array();
        foreach($this->members as $member) {
            if( is_string($member) ) {
                $members_id[] = $member;
            } else if( isset($member->username) && is_string($member->username) ) {
                $members_id[] = $member->username;
            }
        }

        $response = Request::post( $this->api . 'groups.create' )
            ->body(array('name' => $this->name, 'members' => $members_id, 'archived' => $this->archived, 'readonly' => $this->readonly))
            ->send();

        if( self::success($response) ) {
            $this->id = $response->body->group->_id;
            return $response->body->group;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Retrieves the information about the private group, only if you’re part of the group.
     */
    public function info() {
        if (isset($this->id )){
            // If the id is defined, we use it
            $response = Request::get( $this->api . 'groups.info?roomId=' . $this->id )->send();
        } else {
            // If the id is not defined, we use the name
            $response = Request::get( $this->api . 'groups.info?roomName=' . $this->name )->send();
        }

        if( self::success($response)) {
            $this->id = $response->body->group->_id;
            if (isset($response->body->group->archived) && $response->body->group->archived == true) {
                $this->archived = true;
            } else {
                $this->archived = false;
            }
            if (isset($response->body->group->announcement)) {
                $this->announcement = $response->body->group->announcement;
            } else {
                $this->announcement = "";
            }
            return $response->body;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Post a message in this group, as the logged-in user
     */
    public function postMessage( $text ) {
        $message = is_string($text) ? array( 'text' => $text ) : $text;
        if( !isset($message['attachments']) ){
            $message['attachments'] = array();
        }

        $response = Request::post( $this->api . 'chat.postMessage' )
            ->body( array_merge(array('channel' => '#'.$this->name), $message) )
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Set the announcement of this group, as the logged-in user
     */
    public function setAnnouncement( $text ) {
        $message = is_string($text) ? array( 'announcement' => $text ) : $text;

        $response = Request::post( $this->api . 'groups.setAnnouncement' )
            ->body( array_merge(array('roomId' => $this->id), $message) )
            ->send();

        if( self::success($response) ) {
            $this->announcement = $text;
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }


    /**
     * Removes the private group from the user’s list of groups, only if you’re part of the group.
     */
    public function close(){
        $response = Request::post( $this->api . 'groups.close' )
            ->body(array('roomId' => $this->id))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Removes the private group from the user’s list of groups and set it as read-only, only if you’re part of the group.
     */
    public function archive(){
        $response = Request::post( $this->api . 'groups.archive' )
            ->body(array('roomId' => $this->id))
            ->send();

        if( self::success($response) ) {
            $this->archived = true;
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Set group as writable and visible to members, only if you’re part of the group.
     */
    public function unarchive(){
        $response = Request::post( $this->api . 'groups.unarchive' )
            ->body(array('roomId' => $this->id))
            ->send();

        if( self::success($response) ) {
            $this->archived = false;
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Deletes the private group.
     */
    public function delete(){
        $response = Request::post( $this->api . 'groups.delete' )
            ->body(array('roomId' => $this->id))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Removes a user from the private group.
     */
    public function kick( $user ){
        // get group and user ids
        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.kick' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Adds user to the private group.
     */
    public function invite( $user ) {

        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.invite' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Adds owner to the private group.
     */
    public function addOwner( $user ) {

        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.addOwner' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Removes owner of the private group.
     */
    public function removeOwner( $user ) {

        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.removeOwner' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Adds moderator to the private group.
     */
    public function addModerator( $user ) {

        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.addModerator' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Removes moderator of the private group.
     */
    public function removeModerator( $user ) {

        $userId = is_string($user) ? $user : $user->id;

        $response = Request::post( $this->api . 'groups.removeModerator' )
            ->body(array('roomId' => $this->id, 'userId' => $userId))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Lists the users or participants of a private group.
     */
    public function members(){
        $response = Request::get( $this->api . 'groups.members?roomId=' . $this->id )->send();

        if( self::success($response) ) {
            $members = array();
            foreach($response->body->members as $member){
                $user = new User($member->username, null, get_object_vars($member), $this->instanceurl, $this->restroot);
                $user->info();
                $members[] = $user;
            }
            return $members;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Lists the moderators of a private group.
     */
    public function moderators(){
        $response = Request::get( $this->api . 'groups.moderators?roomId=' . $this->id )->send();

        if( self::success($response) ) {
            $moderators = array();
            foreach($response->body->moderators as $moderator){
                $user = new User($moderator->username, null, get_object_vars($moderator), $this->instanceurl, $this->restroot);
                $user->info();
                $moderators[] = $user;
            }
            return $moderators;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Create a link to invite users to this group
     * 	$days :	The number of days that the invite will be valid for.
     *		$maxUses : The number of times that the invite can be used.
     */
    public function getInviteLink($days=0, $maxUses=0){

        $response = Request::post( $this->api . 'findOrCreateInvite' )
            ->body(array('rid' => $this->id, 'days' => $days, 'maxUses' => $maxUses ))
            ->send();

        if( self::success($response) ) {
            return $response->body->url;
        } else {
            throw new RocketChatException($response);
        }
    }

    public function getMessages(){
        $response = Request::get( $this->api . 'groups.messages?roomId=' . $this->id )->send();

        if( self::success($response) ) {
            $messages = array();
            foreach($response->body->messages as $message){
                $messages[$message->_id] = $message;
            }
            return $messages;
        } else {
            throw new RocketChatException($response);
            return false;
        }
    }

    public function cleanHistory($oldest='1970-01-01', $latest='now'){
        $oldest = new \DateTime($oldest);
        $latest = new \DateTime($latest);
        $format = 'Y-m-d\TH:i:s.u\Z';

        $response = Request::post( $this->api . 'rooms.cleanHistory')
            ->body(array('roomId' => $this->id, 'oldest' => $oldest->format($format), 'latest' => $latest->format($format)))->send();
        if( $response->code != 200 || !isset($response->body->success) || $response->body->success != true ) {
            throw new RocketChatException($response);
        }
    }

    public function saveRoomSettings($settings){
        $response = Request::post( $this->api . 'rooms.saveRoomSettings')
            ->body(
                array_merge($settings, array('rid' => $this->id))
            )->send();
        if( $response->code != 200 || !isset($response->body->success) || $response->body->success != true ) {
            throw new RocketChatException($response);
        }
    }

    public function isGroupAlreadyExists(){
        $response = Request::get( $this->api . 'rooms.adminRooms?filter=' . $this->name )->send();
        if( self::success($response) ) {
            foreach($response->body->rooms as $room){
                if($this->name == $room->name) {
                    return true;
                }
            }
            return false;
        } else {
            throw new RocketChatException($response);
        }
    }
}
