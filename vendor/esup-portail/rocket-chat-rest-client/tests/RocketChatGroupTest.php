<?php
use PHPUnit\Framework\TestCase;
use RocketChat\Client;
use RocketChat\Group;
use RocketChat\User;

include_once(dirname(dirname(__FILE__))."/config-test.php");

final class GroupTest extends TestCase
{
    public function testCanInstantiateGroup(): Group
    {
      $group = new Group("testapi_group");
      $this->assertInstanceOf(
          Group::class,
          $group
      );
      return $group;
    }


    /**
    * @depends testCanInstantiateGroup
    */
    public function testCanCreateGroup($group): Group
    {
      global $api_user, $api_pwd;
      $user = new User($api_user, $api_pwd);
      $this->assertTrue($user->login(), "Can't connect. Verify credentials in config.php");
      $this->assertNotFalse($group->create());
      return $group;
    }

    /**
    * @depends testCanCreateGroup
    */
    public function testCanArchiveGroup($group): Group
    {
      $this->assertTrue($group->archive());
      $group->info();
      $this->assertTrue($group->archived);
      return $group;
    }

    /**
    * @depends testCanArchiveGroup
    */
    public function testCanUnarchiveGroup($group): void
    {
      $this->assertTrue($group->unarchive());
      $group->info();
      $this->assertFalse($group->archived);
    }

    /**
    * @depends testCanCreateGroup
    */
    public function testCanInviteGroup($group): Group
    {
      // On ne peut pas inviter un utilisateur inexistant
      $this->assertFalse($group->invite("test_objet_user"));
      $objet_user = new User("test_objet_user", "resu_tejbo_tset", array("email"=>"test_objet_user@test.org", "nickname" => "Test Objet_user"));
      $this->assertNotFalse($objet_user->create(),"We should be able to create a missing user before inviting him");
      $this->assertTrue($group->invite($objet_user), "Can't invite a user through object");

      $members = $group->members();
      $test_objet_user_exists = false;
      foreach ($members as $member) {
        if ($member->username == 'test_objet_user'){
          $test_objet_user_exists = true;
        }
      }
      $this->assertTrue($test_objet_user_exists);
      return $group;
    }

    /**
    * @depends testCanInviteGroup
    */
    public function testCanKickGroup($group): void
    {
      $objet_user = new User("test_objet_user");
      $objet_user->info();
      $this->assertTrue($group->kick($objet_user, true), "Can't kick user from group");

      $members = $group->members();
      $test_objet_user_exists = false;
      foreach ($members as $member) {
        if ($member->username == 'test_objet_user') {
            $test_objet_user_exists = true;
        }
      }
      $this->assertFalse($test_objet_user_exists);

      // Cleaning
      $objet_user->info();
      $objet_user->delete();
    }

    /**
    * @depends testCanCreateGroup
    */
    public function testCanMakeAnnouncement($group): void
    {
        $this->assertTrue($group->setAnnouncement("Test announcement", true), "Can't set announcement");
        $this->assertIsString($group->announcement, "Announcement is not a string");
        $this->assertTrue($group->setAnnouncement("", true), "Can't empty announcement");
        $this->assertStringMatchesFormat("", $group->announcement, "Announcement is not an empty string");
    }

    /**
    * @depends testCanCreateGroup
    */
    public function testCanCreateInviteLink($group): void
    {
      $this->assertStringMatchesFormat( ROCKET_CHAT_INSTANCE.'/invite/%s',$group->getInviteLink());
    }

    /**
    * @depends testCanCreateGroup
    */
    public function testCanDeleteGroup($group): void
    {
        $this->assertTrue($group->delete());
    }
}
?>
