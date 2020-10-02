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
			->expectsJson();
		Request::ini( $tmp );
	}

	/**
	* Get version information. This simple method requires no authentication.
	*/
	public function version() {
		$response = \Httpful\Request::get( $this->api . 'info' )->send();
		return $response->body->info->version;
	}

	/**
	* Quick information about the authenticated user.
	*/
	public function me() {
		$response = Request::get( $this->api . 'me' )->send();

		if( $response->body->status != 'error' ) {
			if( isset($response->body->success) && $response->body->success == true ) {
				return $response->body;
			}
		} else {
			echo( $response->body->message . "\n" );
			return false;
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

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			return $response->body->users;
		} else {
			echo( $response->body->error . "\n" );
			return false;
		}
	}

	/**
	* List the private groups the caller is part of.
	*/
	public function list_groups() {
		$response = Request::get( $this->api . 'groups.list' )->send();

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			$groups = array();
			foreach($response->body->groups as $group){
				$groups[] = new Group($group);
			}
			return $groups;
		} else {
			echo( $response->body->error . "\n" );
			return false;
		}
	}

	/**
	* List all the private groups
	*/
	public function list_groups_all() {
		$response = Request::get( $this->api . 'groups.listAll' )->send();

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			$groups = array();
			foreach($response->body->groups as $group){
				$groups[] = new Group($group);
			}
			return $groups;
		} else {
			var_dump( $response );
			//echo( $response->body->error . "\n" );
			return false;
		}
	}

	/**
	* List the channels the caller has access to.
	*/
	public function list_channels() {
		$response = Request::get( $this->api . 'channels.list' )->send();

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			$groups = array();
			foreach($response->body->channels as $group){
				$groups[] = new Channel($group);
			}
			return $groups;
		} else {
			echo( $response->body->error . "\n" );
			return false;
		}
	}

	public function user_info( $user, $verbose = false ) {
		if (isset($user->id )){
			// If the id is defined, we use it
			$response = Request::get( $this->api . 'users.info?userId=' . $user->id )->send();
		} else {
			// If the id is not defined, we use the name
			$response = Request::get( $this->api . 'users.info?username=' . $user->username )->send();
		}

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			return $response->body->user;
		} else {
			if ($verbose) {
				echo( $response->body->error . "\n" );
			}
			return false;
		}
	}

}
