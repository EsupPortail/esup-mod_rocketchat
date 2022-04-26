<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

/**
 * Class Channel
 */
class Channel extends Client
{

	public $id;
	public $name;
	public $members = array();

	public function __construct($name, $members = array(), $instanceurl = null, $restroot = null){
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
		foreach ($members as $member)
		{
			if (is_a($member, '\RocketChat\User'))
			{
				$this->members[] = $member;
			}
			else if (is_string($member))
			{
				// TODO
				$this->members[] = new User($member);
			}
		}
	}

	/**
	 * Creates a new channel.
	 */
	public function create()
	{
		// get user ids for members
		$members_id = array();
		foreach ($this->members as $member)
		{
			if (is_string($member))
			{
				$members_id[] = $member;
			}
			else if (isset($member->username) && is_string($member->username))
			{
				$members_id[] = $member->username;
			}
		}

		$response = Request::post($this->api . 'channels.create')
			->body(array('name' => $this->name, 'members' => $members_id))
			->send();

		if (self::success($response))
		{
			$this->id = $response->body->channel->_id;
			return $response->body->channel;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	
    /**
     * Retrieves the information about the channel.
     */
    public function info()
    {
        //https://rocketchat-dev.di.unistra.fr/api/v1/channels.info?roomId=nex7SLn3Hrb4XcAD5
        $response = Request::get($this->api . 'channels.info?roomId=' . $this->id)->send();
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

    /**
     * Retrieves the information about the channel.
     */
    public function roles()
    {
        $response = Request::get($this->api . 'channels.roles?roomId=' . $this->id)->send();
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

    /**
     * Retrieves the information about the channel.
     */
    public function members()
    {
        $url = $this->api . 'channels.members?roomId='.$this->id;
        $response = Request::get($url)->send();
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

	/**
	 * Post a message in this channel, as the logged-in user
	 *
	 * @param $text
	 * @param null $alias
	 * @param $emoji
	 * @param null $avatar
	 *
	 * @return bool
	 */
	public function postMessage($text, $alias = null, $emoji = null, $avatar = null)
	{
		$body = array();
		(substr($this->name, 0, 1) == "#" ? $body["channel"] = $this->name : $body["roomId"] = $this->id);
		$body["text"] = $text;
		if ($alias) $body["alias"] = $alias;
		if ($alias) $body["emoji"] = $emoji;
		if ($alias) $body["avatar"] = $avatar;

		$response = Request::post($this->api . 'chat.postMessage')
			->body($body)
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	public function getAllMessages(){
		$response = Request::get( $this->api . 'channels.history?roomId=' . $this->id )->send();

		if( self::success($response) ) {
			$messages = array();
			foreach($response->body->messages as $message){
				$messages[$message->_id] = $message;
			}
			return messages;
		} else {
			throw new RocketChatException($response);
		}
	}

	public function deleteMessage($messageid,$verbose){
		$response = Request::get( $this->api . 'chat.delete?roomId=' . $this->id . '&messageId=' . $messageid )->send();

		if( self::success($response) ) {
			return true;
		} else {
			throw new RocketChatException($response);
		}
	}

	public function clean($verbose){
		$messages = $this->getAllMessages($verbose);
		if($messages){
			foreach (array_keys($messages) as $messageid){
				$this->deleteMessage($messageid, $verbose);
			}
		}
	}

	/**
	 * Removes the channel from the user’s list of channels.
	 */
	public function close()
	{
		$response = Request::post($this->api . 'channels.close')
			->body(array('roomId' => $this->id))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	/**
	 * Delete the channel
	 */
	public function delete()
	{
		$response = Request::post($this->api . 'channels.delete')
			->body(array('roomId' => $this->id))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	/**
	 * Removes a user from the channel.
	 */
	public function kick($user)
	{
		// get channel and user ids
		$userId = is_string($user) ? $user : $user->id;

		$response = Request::post($this->api . 'channels.kick')
			->body(array('roomId' => $this->id, 'userId' => $userId))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	/**
	 * Adds user to channel.
	 */
	public function invite($user)
	{

		$userId = is_string($user) ? $user : $user->id;

		$response = Request::post($this->api . 'channels.invite')
			->body(array('roomId' => $this->id, 'userId' => $userId))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	/**
	 * Adds owner to the channel.
	 */
	public function addOwner($user)
	{

		$userId = is_string($user) ? $user : $user->id;

		$response = Request::post($this->api . 'channels.addOwner')
			->body(array('roomId' => $this->id, 'userId' => $userId))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

	/**
	 * Removes owner of the channel.
	 */
	public function removeOwner($user)
	{

		$userId = is_string($user) ? $user : $user->id;

		$response = Request::post($this->api . 'channels.removeOwner')
			->body(array('roomId' => $this->id, 'userId' => $userId))
			->send();

		if (self::success($response))
		{
			return true;
		}
		else
		{
			throw new RocketChatException($response);
		}
	}

}

