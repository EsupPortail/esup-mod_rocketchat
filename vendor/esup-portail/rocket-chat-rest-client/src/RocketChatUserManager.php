<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class UserManager extends Client {
	public $adminusername;
	private $adminpassword;
	public $adminid;

	public function __construct($adminusername, $adminpassword = null, $instanceurl = null, $restroot = null){
		if(!is_null($instanceurl) && !is_null($restroot)){
			parent::__construct($instanceurl, $restroot);
		}else {
			parent::__construct();
		}
		$this->adminusername = $adminusername;
		$this->adminpassword = $adminpassword;
		$this->login();
	}

	/**
	* Authenticate with the REST API.
	*/
	public function login() {
		$response = Request::post( $this->api . 'login' )
			->body(array( 'user' => $this->adminusername, 'password' => $this->adminpassword ))
			->send();

		if( $response->code == 200 && isset($response->body->status) && $response->body->status == 'success' ) {
		    // save auth token for future requests
            $tmp = Request::init()
                ->addHeader('X-Auth-Token', $response->body->data->authToken)
                ->addHeader('X-User-Id', $response->body->data->userId);
            Request::ini( $tmp );
            $this->adminid = $response->body->data->userId;
            return true;
		}
        $this->logger->error( $response->body->error . "\n" );
		return false;
	}

	public function logout() {
		$response = Request::post( $this->api . 'logout' )
			->send();

		if( $response->code == 200 && isset($response->body->status) && $response->body->status == 'success' ) {
			Request::resetIni();
			return true;
		} else {
			$this->logger->error( $response->body->message . "\n" );
			return false;
		}
	}

	/**
	* Gets a user’s information, limited to the caller’s permissions.
	*/
	public function info($user, $verbose = false ) {
		if (isset($user->id )){
			// If the id is defined, we use it
			$response = Request::get( $this->api . 'users.info?userId=' . $user->id )->send();
		} else {
			// If the id is not defined, we use the name
			$response = Request::get( $this->api . 'users.info?username=' . $user->username )->send();
		}

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			return $response->body;
		} else {
			if ($verbose) {
				$this->logger->error( $response->body->error . "\n" );
			}
			return false;
		}
	}

	/**
	 * Create a new user.
	 */
	public function create($user, $verbose = false ) {
		$info = $this->info($user);
		if ($info and isset($info->user)) return $info->user;

		$response = Request::post( $this->api . 'users.create' )
			->body(array(
				'name' => $user->nickname,
				'email' => $user->email,
				'username' => $user->username,
				'password' => $user->password,
			))
			->send();

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			return $response->body->user;
		} else {
			if ($verbose) {
				$this->logger->error( $response->body->error . "\n" );
			}
			return false;
		}
	}

	/**
	 * Deletes an existing user.
	 */
	public function delete($userid) {
		$response = Request::post( $this->api . 'users.delete' )
			->body(array('userId' => $userid))
			->send();

		if( $response->code == 200 && isset($response->body->success) && $response->body->success == true ) {
			return true;
		} else {
			$this->logger->error( $response->body->error . "\n" );
			return false;
		}
	}
}
