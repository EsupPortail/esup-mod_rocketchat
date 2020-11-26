<?php

namespace RocketChat;

use Httpful\Request;

class Client{

	public $api;
	protected $instanceurl;
	public $restroot;

	function __construct(){
		$args = func_get_args();
		if( count($args) == 2){
			$this->api = $args[0].$args[1];
			$this->instanceurl = $args[0];
			$this->restroot = $args[1];
		}else{
			$this->api = ROCKET_CHAT_INSTANCE . REST_API_ROOT;
			$this->instanceurl = ROCKET_CHAT_INSTANCE;
			$this->restroot = REST_API_ROOT;
		}
		// set template request to send and expect JSON
		$tmp = Request::init()
			->sendsJson()
			->parseWith(
				function($body){
					$body = self::stripBom($body);
					if (empty($body))
						return null;
					$parsed = json_decode($body, false);
					if (is_null($parsed) && 'null' !== strtolower($body)){
						// Search for html and title
						$title = 0;
						$dom = new \DOMDocument();
						if($dom->loadHTML($body)){
							$title = $dom->getElementsByTagName('title');
							if(!empty($title) && $title->count() >0 && !empty($title->item(0)->textContent)){
								$title = intval(preg_replace('/[^0-9]/', '', $title->item(0)->textContent));
							} else {
								$title = -1;
							}

						}
						throw new RocketChatException('Error while parsing json due to : ' . $body, $title);
					}
					return $parsed;
				}
			);
		Request::ini( $tmp );
	}

	/**
	* Get version information. This simple method requires no authentication.
	*/
	public function version() {
		$response = \Httpful\Request::get( $this->api . 'info' )->send();
		if(self::success($response)) {
			return $response->body->info->version;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* Quick information about the authenticated user.
	*/
	public function me() {
		$response = Request::get( $this->api . 'me' )->send();

		if(self::success($response)) {
			if( isset($response->body->success) && $response->body->success == true ) {
				return $response->body;
			}
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* List all of the users and their information.
	*
	* Gets all of the users in the system and their information, the result is
	* only limited to what the callee has access to view.
	*/
	public function list_users(){
		$response = Request::get( $this->api . 'users.list' )->send();
		if(self::success($response)) {
			return $response->body->users;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* List the private groups the caller is part of.
	*/
	public function list_groups() {
		$response = Request::get( $this->api . 'groups.list' )->send();

		if( self::success($response) ) {
			$groups = array();
			foreach($response->body->groups as $group){
				$groups[] = new Group($group);
			}
			return $groups;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* List all the private groups
	*/
	public function list_groups_all() {
		$response = Request::get( $this->api . 'groups.listAll' )->send();

		if( self::success($response) ) {
			$groups = array();
			foreach($response->body->groups as $group){
				$groups[] = new Group($group);
			}
			return $groups;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* List the channels the caller has access to.
	*/
	public function list_channels() {
		$response = Request::get( $this->api . 'channels.list' )->send();

		if( self::success($response) ) {
			$groups = array();
			foreach($response->body->channels as $group){
				$groups[] = new Channel($group);
			}
			return $groups;
		} else {
			throw new RocketChatException($response);
		}
	}

	public function user_info( $user) {
		if (isset($user->id )){
			// If the id is defined, we use it
			$response = Request::get( $this->api . 'users.info?userId=' . $user->id )->send();
		} else {
			// If the id is not defined, we use the name
			$response = Request::get( $this->api . 'users.info?username=' . $user->username )->send();
		}

		if( self::success($response) ) {
			return $response->body->user;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	 * @param \Httpful\Response $response
	 * @return bool
	 */
	protected static function success(\Httpful\Response $response) {
		return $response->code == 200 &&
			((isset($response->body->success) && $response->body->success == true)
				|| (isset($response->body->status) && $response->body->status == 'success')
			);
	}
	protected static function stripBom($body)
	{
		if ( substr($body,0,3) === "\xef\xbb\xbf" )  // UTF-8
			$body = substr($body,3);
		else if ( substr($body,0,4) === "\xff\xfe\x00\x00" || substr($body,0,4) === "\x00\x00\xfe\xff" )  // UTF-32
			$body = substr($body,4);
		else if ( substr($body,0,2) === "\xff\xfe" || substr($body,0,2) === "\xfe\xff" )  // UTF-16
			$body = substr($body,2);
		return $body;
	}

}
