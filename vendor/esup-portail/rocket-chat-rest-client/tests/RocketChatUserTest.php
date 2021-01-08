<?php

namespace RocketChat;

use PHPUnit\Framework\TestCase;
use RocketChat\Client;
use RocketChat\User;

include_once(dirname(dirname(__FILE__))."/config-test.php");

final class UserTest extends TestCase
{
	public function testCanCreateUserWithoutPassword(): void
	{
		global $api_user;
		$this->assertInstanceOf(
			User::class,
			new User($api_user)
		);
	}

	public function testCanCreateUserWithPassword(): User
	{
		global $api_user, $api_pwd;
		$user = new User($api_user, $api_pwd);
		$this->assertInstanceOf(
			User::class,
			$user
		);
		return $user;
	}

	/**
	* @depends testCanCreateUserWithPassword
	*/
	public function testCanLogin($api_user): void
	{

		$this->assertTrue($api_user->login());
	}

	/**
	* @depends testCanLogin
	* @dataProvider userProvider
	*/
	public function testCanCreateUser($username, $password, $attributes, $expected): void
	{
		$user = new User($username, $password, $attributes);
		$this->assertInstanceOf(
		  User::class,
		  $user
		);
		if ($expected) {
		  $this->assertNotFalse($user_returned = $user->create());
		  $this->assertSame($username, $user_returned->username);
		  $this->assertSame($attributes["email"], $user_returned->emails[0]->address);
		  $this->assertSame($attributes["nickname"], $user_returned->name);
		} else {
		  $this->expectException("RocketChat\RocketChatException");
			$user->create();
    }
  }

    /**
    * @depends testCanCreateUser
    * @dataProvider userProvider
    */
    public function testCanDeleteUser($username, $password, $attributes, $expected): void
    {
      $user = new User($username);
      if ($expected){
        $user->info();
        $this->assertTrue($user->delete());
      } else {
			  $this->expectException("RocketChat\RocketChatException");
        $user->info();
      }
    }

    /**
    * Provides data for testCanCreateUser et testCanDeleteUser
    */
    public function userProvider(){
      return array(
              array(
                "testapi_user1",
                "pwd1",
                array(
                  "email" => "user1@test.org",
                  "nickname" => "User1 for API test"
                ),
                true
              ),
              array(
                "testapi_user2",
                null,
                array(
                  "email" => "user2@test.org",
                  "nickname" => "User2 for API test"
                ),
                false
              ),
              array(
                "testapi_user3",
                "pwd3",
                array(
                  "email" => null,
                  "nickname" => null
                ),
                false
              ),

            );
    }



}
?>
