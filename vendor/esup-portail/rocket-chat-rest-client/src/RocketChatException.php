<?php
namespace RocketChat;

class RocketChatException extends \Exception {
	public function __construct($response, $code = 0, Exception $previous = null) {
		$message = '';
		if (is_string($response))
		{
			$message = strip_tags($response);
		} else {
			$message = isset($response->body->error) ? $response->body->error : $response->body->message;
			$code = $response->code;
		}
		parent::__construct($message, $code, $previous);
	}
}
