<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class Room extends Client
{
    public $id;
    public $name;
    public $usersCount;
    public $msgs;
    public $customFields;
    public $broadcast;
    public $encrypted;

    public function __construct($name, $instanceurl = null, $restroot = null){
        if(!is_null($instanceurl) && !is_null($restroot)){
            parent::__construct($instanceurl, $restroot);
        }else {
            parent::__construct();
        }
        if (is_string($name))
        {
            $this->name = $name;
        }
        else if (isset($name->_id))
        {
            $this->name = $name->name;
            $this->id = $name->_id;
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

    
    /**
     * Retrieves the information about the channel.
     */
    public function roles()
    {
        $response = Request::get($this->api . 'room.roles?roomId=' . $this->id)->send();
        if (self::success($response))
        {
            $this->id = $response->body->channel->_id;
            return $response->body;
        }
        else
        {
            throw new RocketChatException($response);
        }
    }

}