<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class User extends Client {
	public $username;
	private $password;
	public $id;
	public $nickname;
	public $email;

	public function __construct($username, $password = null, $fields = array(), $instanceurl = null, $restroot = null){
		if(!is_null($instanceurl) && !is_null($restroot)){
			parent::__construct($instanceurl, $restroot);
		}else {
			parent::__construct();
		}
		$this->username = $username;
		$this->password = $password;
		if( isset($fields['_id']) ) {
			$this->id = $fields['_id'];
		}
		if( isset($fields['nickname']) ) {
			$this->nickname = $fields['nickname'];
		}
		if( isset($fields['email']) ) {
			$this->email = $fields['email'];
		}
	}

	/**
	* Authenticate with the REST API.
	*/
	public function login($save_auth = true) {
		$response = Request::post( $this->api . 'login' )
			->body(array( 'user' => $this->username, 'password' => $this->password ))
			->send();

		if( $response->code == 200 && isset($response->body->status) && $response->body->status == 'success' ) {
			if( $save_auth) {
				// save auth token for future requests
				$tmp = Request::init()
					->addHeader('X-Auth-Token', $response->body->data->authToken)
					->addHeader('X-User-Id', $response->body->data->userId);
				Request::ini( $tmp );
			}
			$this->id = $response->body->data->userId;
			return true;
		} else {
			throw new RocketChatException($response);
		}
	}

	public function logout() {
		$response = Request::post( $this->api . 'logout' )
			->send();

		if( $response->code == 200 && isset($response->body->status) && $response->body->status == 'success' ) {
			Request::resetIni();
			return true;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* Gets a user’s information, limited to the caller’s permissions.
	*/
	public function info() {
		if (isset($this->id )){
			// If the id is defined, we use it
			$response = Request::get( $this->api . 'users.info?userId=' . $this->id )->send();
		} else {
			// If the id is not defined, we use the name
			$response = Request::get( $this->api . 'users.info?username=' . $this->username )->send();
		}

		if( self::success($response) ) {
			$this->id = $response->body->user->_id;
			$this->nickname = $response->body->user->name;
			if (isset($response->body->user->emails[0])) {
				$this->email = $response->body->user->emails[0]->address;
			}
			return $response->body;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* Create a new user.
	*/
	public function create() {
		try {
				$info = $this->info();
				return $info;
		} catch (RocketChatException $rce) {
			# L'utilisateur n'existe pas, on va le créer
		}
		# If the user doesn't exist, we create it
		$response = Request::post( $this->api . 'users.create' )
			->body(array(
				'name' => $this->nickname,
				'email' => $this->email,
				'username' => $this->username,
				'password' => $this->password,
			))
			->send();

		if( self::success($response) ) {
			$this->id = $response->body->user->_id;
			return $response->body->user;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* Deletes an existing user.
	*/
	public function delete() {

		// get user ID if needed
		if( !isset($this->id) ){
			$this->me();
		}
		$response = Request::post( $this->api . 'users.delete' )
			->body(array('userId' => $this->id))
			->send();

		if( self::success($response) ) {
			return true;
		} else {
			throw new RocketChatException($response);
		}
	}

	/**
	* Print information about the user
	*/
	public function print_info($verbose=false) {
		if ($verbose) {
			echo "<p><strong>".$this->nickname."</strong> <em>@".$this->username."</em><br/>";
			echo "id : ".$this->id."<br/>email : ".$this->email;
			echo "</p>";
		} else {
			echo $this->nickname." - @".$this->username." (".$this->email.")<br/>";
		}
	}

}
