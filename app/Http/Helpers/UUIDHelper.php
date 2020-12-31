<?php

namespace App\Http\Helpers;

class UUIDHelper {

	public static function generateID() {
		$micros	= explode(' ', str_replace('.', '', microtime()));
		$time	= $micros[0].$micros[1];
		$rand	= random_int(0, 999);
		$uid	= $rand.$time;
		$str	= str_random((32-strlen($uid)));
		
		return $uid.$str;
	}
	
	public static function docNumber($serviceLetter='') {
		$o			= ($serviceLetter != '' ? '-'.$serviceLetter : '').'o2o-';
		$dateTime	= date('Ymd-His-');
		$rand		= str_random(4);
		
		return $o.$dateTime.$rand;
	}
	
}
