<?php namespace App\Http\Helpers;

use App\Http\Helpers\WebCurl;

class SmsSender {
	
	var $to, $message;
	
	function __construct($to, $message) {
		$this->to = $to;
		$this->message = $message;
	}
	
	public function send() {
		$curl = new WebCurl(['Content-Type: application/json']);
		$url = 'http://smsdev.clientname.id/sms/send';
		$params = json_encode(['to' => $this->to, 'message' => $this->message, 'token' => '0weWRasJL234wdf1URfwWxxXse304']);
		$response = $curl->post($url, $params);
		
		return strpos($response, 'Status=1') != false;
	}
	
}
