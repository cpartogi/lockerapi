<?php

namespace App\Http\Helpers;

class UserTokenHelper {
	
	var $userId = '';
	
	public function isAuthorized() {
		return $this->userId != '';
	}
	
	public function getUserId() {
		$json			= array();//json_decode(file_get_contents($this->getToken()), true);
		$this->userId	= isset($json['user_id']) ? $json['user_id'] : '3424092323u423h2hrhw';
		
		return $this->userId;
	}
	
	public function getToken() {
		$header		= isset($_SERVER['Authorization']) ? $_SERVER['Authorization'] : '';
		if($header != '') {
			$auths	= explode('Basic ', $header);
			if(!empty($auths) && count($auths) == 2) {
				return base64_decode($auths[1]);
			}
		}
		
		return '';
	}
	
}
