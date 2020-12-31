<?php

namespace App\Http\Helpers;

class NotificationHelper {
	
	var $errorMsgs		= array();
	var $errorMsg		= 'OK';
	var $errorCode		= 200;
	var $data;
	
	public function addErrorMessage($key, $message) {
		$this->errorMsgs[$key]	= $message;
		$this->setDataError();		
	}
	
	public function isOK() {
		return $this->errorCode == 200;
	}

	public function ParseError()
	{

	}

	public function FatalError()
	{

	}

	public function urlencrypt($enc)
	{

	}
	
	public function urldecrypt($dec)
	{

	}

	
	public function setOK($total, $data) {
		$this->errorCode	= 200;
		$this->data			= $data;
		$this->total		= $total;
	}
	
	public function setDataError($errorMsg='') {
		$this->errorCode	= 401;
		$this->data			= array();
		if (empty($errorMsg))  $this->errorMsg		= 'Warning error missing parameter';
		else $this->errorMsg = $errorMsg;
	}
	
	public function setUnauthorized() {
		$this->errorCode	= 400;
		$this->errorMsg		= 'Unauthorized';
	}
	
	public function setInternalServerError() {
		$this->errorCode	= 500;
		$this->errorMsg		= 'Internal Server Error';
	}
	
	public function build() {
		$notif						= array();
		$notif['response']			= array();
		$notif['response']['code']	= $this->errorCode;

		if(!empty($this->errorMsgs))
			$notif['response']['messages']	= $this->errorMsgs;
		else
			$notif['response']['message']	= $this->errorMsg;
		
		if($this->isOK())
			$notif['total_page']			= $this->total;
			$notif['data']					= $this->data;

		return $notif;
	}
	
}
