<?php

namespace RocketChat;

use Httpful\Request;
use RocketChat\Client;

class UserManager extends Client {
    public function __construct($tokenmode, $adminusernameorid, $adminpasswordortoken = null, $instanceurl = null, $restroot = null){
        if(!is_null($instanceurl) && !is_null($restroot)){
            parent::__construct($instanceurl, $restroot);
        }else {
            parent::__construct();
        }
        if ($tokenmode){
            $this->prepare_connection_with_token($adminusernameorid, $adminpasswordortoken);
        } else {
            $this->login($adminusernameorid, $adminpasswordortoken);
        }
    }
    public function prepare_connection_with_token($userid, $authtoken){
        // Save auth token for future requests
        $tmp = Request::init()
            ->addHeader('X-Auth-Token', $authtoken)
            ->addHeader('X-User-Id', $userid);
        Request::ini( $tmp );
        return true;
    }

    /**
     * Authenticate with the REST API.
     */
    public function login_token($token){
        $response = Request::post( $this->api . 'login' )
            ->body(array( 'resume' => $token))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }
    public function login($adminusername, $adminpassword) {
        $response = Request::post( $this->api . 'login' )
            ->body(array( 'user' => $adminusername, 'password' => $adminpassword ))
            ->send();

        if( self::success($response) ) {
            // save auth token for future requests
            $tmp = Request::init()
                ->addHeader('X-Auth-Token', $response->body->data->authToken)
                ->addHeader('X-User-Id', $response->body->data->userId);
            Request::ini( $tmp );
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    public function logout() {
        $response = Request::post( $this->api . 'logout' )
            ->send();

        if( self::success($response) ) {
            Request::resetIni();
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Gets a user’s information, limited to the caller’s permissions.
     */
    public function info($user) {
        if (isset($user->id )){
            // If the id is defined, we use it
            $response = Request::get( $this->api . 'users.info?userId=' . $user->id )->send();
        } else {
            // If the id is not defined, we use the name
            $response = Request::get( $this->api . 'users.info?username=' . $user->username )->send();
        }

        if( self::success($response) ) {
            return $response->body;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Create a new user.
     */
    public function create($user) {
        try{
            $info = $this->info($user);
            if ($info and isset($info->user)){
                return $info->user;
            }
        } catch(RocketChatException $e){
            // No error trigger here
        }
        $parameters = array(
            'name' => $user->nickname,
            'email' => $user->email,
            'username' => $user->username,
            'password' => $user->password,
        );
        if (property_exists($user, 'requirePasswordChange')) {
            $parameters['requirePasswordChange'] =$user->requirePasswordChange;
        }
        if (property_exists($user, 'setRandomPassword')) {
            $parameters['setRandomPassword'] =$user->setRandomPassword;
        }
        if (property_exists($user, 'sendWelcomeEmail')) {
            $parameters['sendWelcomeEmail'] =$user->sendWelcomeEmail;
        }
        if (property_exists($user, 'verified')) {
            $parameters['verified'] =$user->verified;
        }
        $response = Request::post( $this->api . 'users.create' )
            ->body($parameters)
            ->send();

        if( self::success($response) ) {
            return $response->body->user;
        } else {
            throw new RocketChatException($response);
        }
    }

    /**
     * Deletes an existing user.
     */
    public function delete($userid) {
        $response = Request::post( $this->api . 'users.delete' )
            ->body(array('userId' => $userid))
            ->send();

        if( self::success($response) ) {
            return true;
        } else {
            throw new RocketChatException($response);
        }
    }
}
