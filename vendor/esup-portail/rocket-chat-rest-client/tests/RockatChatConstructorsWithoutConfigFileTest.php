<?php
namespace RocketChat;

use PHPUnit\Framework\TestCase;
use RocketChat\Client;



final class ConstructorsWithoutConfigFileTest extends TestCase
{

    public function testCanInstantiateClient(): void
    {
        $this->assertInstanceOf(
            Client::class,
            new Client('https://chat.yourorganisation.org', '/api/v1/')
        );
    }
    public function testCanInstantiateGroup(): Group
    {
        $group = new Group("testapi_group", array(), 'https://chat.yourorganisation.org', '/api/v1/');
        $this->assertInstanceOf(
            Group::class,
            $group
        );
        return $group;
    }


}
?>
