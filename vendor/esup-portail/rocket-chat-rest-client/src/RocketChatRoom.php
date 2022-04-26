<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class Room extends Client
{
    public $name;
    public $usersCount;
    public $msgs;
    public $customFields;
    public $broadcast;
    public $encrypted;

    public function __construct($fields = array(), $instanceurl = null, $restroot = null){
        if(!is_null($instanceurl) && !is_null($restroot)){
            parent::__construct($instanceurl, $restroot);
        }else {
            parent::__construct();
        }
        if( isset($fields['name']) ) {
            $this->name = $fields['name'];
        }
        if( isset($fields['$usersCount']) ) {
            $this->usersCount = $fields['$usersCount'];
        }
        if( isset($fields['msgs']) ) {
            $this->msgs = $fields['msgs'];
        }
        if( isset($fields['customFields']) ) {
            $this->customFields = $fields['customFields'];
        }
        if( isset($fields['broadcast']) ) {
            $this->broadcast = $fields['broadcast'];
        }
        if( isset($fields['encrypted']) ) {
            $this->encrypted = $fields['encrypted'];
        }
    }


    /**
     * Retrieves the information about the channel.
     */
    public function info()
    {
        $response = Request::get($this->api . 'rooms.info?roomId=' . $this->id)->send();
        if (self::success($response))
        {
            //$this->id = $response->body->channel->_id;
            return $response->body;
        }
        else
        {
            throw new RocketChatException($response);
        }
    }

}