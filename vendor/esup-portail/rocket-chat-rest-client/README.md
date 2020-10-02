# Rocket Chat REST API client in PHP

Use this client if you need to connect to Rocket Chat with a software written
in PHP, such as WordPress or Drupal.

## How to use

This Rocket Chat client is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "EsupPortail/rocket-chat-rest-client": "dev-master"
    }
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

Then, import the `autoload.php` from your `vendor` folder.

#### Configure your Rocket.Chat instance
you have to way to do that
##### config file
* You have to copy the file config-sample.php to config.php and complete it with the information of your Rocket Chat instance

##### passing instance url and rest root to constructors
* as optional arguments

#### classes instances examples

##### Initiate Connection
```php
// Initiate the connection
$api = new \RocketChat\Client(); // with config file or add instance url and rest root as parameters without config file
echo $api->version(); echo "\n";

// login as the main admin user
$admin = new \RocketChat\User($api_user, $api_pwd); // with config file or add instance url and rest root as parameters without config file
if( $admin->login() ) {
	echo "admin user logged in\n";
};
$admin->info();
echo "I'm {$admin->nickname} ({$admin->id}) "; echo "\n";
```

#### Manage user
```php
// create a new user
$newuser = new \RocketChat\User('new_user_name', 'new_user_password', array(
	'nickname' => 'New user nickname',
	'email' => 'newuser@example.org',
)); // with config file or add instance url and rest root as parameters without config file
if( !$newuser->login(false) ) {
	// actually create the user if it does not exist yet
  $newuser->create();
}
echo "user {$newuser->nickname} created ({$newuser->id})\n";
```

##### Post a message
```php
// create a new channel
$channel = new \RocketChat\Channel( 'my_new_channel', array($newuser, $admin) ); // with config file or add instance url and rest root as parameters without config file
$channel->create();
// post a message
$channel->postMessage('Hello world');
```

##### Create  group
___Warning___ : To be able to manipulate groups, the admin user used to create groups shall remain a member of the group.
```php
$group = new Group("testapiRC_group"); // with config file or add $members = array(), $options = array(), $instanceurl = null, $restroot = null  without config file 
$group->create();
echo "The group ".$group->name." is created with the id ".$group->id;
```

##### Create user
```php
$user = new User("testapiRC_user","dummyPwd", array("email" => "test@test.org", "nickname" => "Test API user")); // with config file or add instance url and rest root as parameters without config file
$user->create();
echo "The user ".$user->nickname." is created with the id ".$user->id;
```

##### Invite user to group
```php
$group->invite($user);
echo "Members of group ".$group->name." :<br/>";
foreach ($group->members() as $member) {
	$member->print_info();
}
```

##### Set user as moderator
```php
$group->addModerator($user);
```

##### Archive and unarchive group
```php
$group->archive();
$group->unarchive();
```

##### Kick user from group
```php
$group->kick($user);
echo "Members of group ".$group->name." :<br/>";
foreach ($group->members() as $member) {
	$member->print_info();
}
```

##### Get invite link
```php
$inviteLink = $group->getInviteLink();
echo "Send this link to your friends : ".$inviteLink;
```

##### Delete user and group
```php
$user->delete();
$group->delete();
```

## Credits
This REST client uses the excellent [Httpful](http://phphttpclient.com/) PHP library by [Nate Good](https://github.com/nategood) ([github repo is here](https://github.com/nategood/httpful)).
