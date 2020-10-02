<?php
namespace RocketChat;

use PHPUnit\Framework\TestCase;
use RocketChat\Client;


include_once(dirname(dirname(__FILE__))."/config-test.php");

final class ClientTest extends TestCase
{
	public function testCanCreateConnexionWithConfigFile(): void
	{
		$this->assertInstanceOf(
			Client::class,
			new Client()
		);
	}
}
?>
