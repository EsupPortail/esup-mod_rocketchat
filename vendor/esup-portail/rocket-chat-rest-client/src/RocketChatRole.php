<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class Role extends Client
{
    public $username;
    public $type;
    public $status;
    public $active;
    public $name;

    public function __construct($fields = array(), $instanceurl = null, $restroot = null){
        if(!is_null($instanceurl) && !is_null($restroot)){
            parent::__construct($instanceurl, $restroot);
        }else {
            parent::__construct();
        }
        if( isset($fields['$username']) ) {
            $this->username = $fields['$username'];
        }
        if( isset($fields['type']) ) {
            $this->type = $fields['type'];
        }
        if( isset($fields['status']) ) {
            $this->status = $fields['status'];
        }
        if( isset($fields['active']) ) {
            $this->active = $fields['active'];
        }
        if( isset($fields['name']) ) {
            $this->name = $fields['name'];
        }
    }


    /**
     * Gets a user’s information, limited to the caller’s permissions.
     */
    public function getUserInRole() {
        $response = Request::get( $this->api . 'users.getUsersInRole' )->send();
        if( self::success($response) ) {
            return $response->body;
        } else {
            throw new RocketChatException($response);
        }
    }

}