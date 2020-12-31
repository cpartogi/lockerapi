<?php
/**
 * Doku's Global Function
 */
namespace App\Http\Helpers\finclude\Core;

class DokuLibrary {

	public static function doCreateWords($data){				
		if(!empty($data['deviceid'])){			
		 	if(!empty($data['pairing_code'])){
		 		$raw = $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency']   .$data['token'].$data['pairing_code'].$data['deviceid'];
				$sha1 = sha1($raw);		
				return $sha1;
		 	}else{
		 		$raw = $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'] . $data['device_id'];
				$sha1 = sha1($raw);
				return $sha1;
		 	}

		 }else if(!empty($data['pairing_code'])){
		 		$raw = $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'] . $data['token'] . $data['pairing_code'];
				$sha1 = sha1($raw);
				return $sha1;

		 }else if(!empty($data['currency'])){		 	
		 	$raw = $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'];
		 	$sha1 = sha1($raw);		 	
		 	return $sha1;

		 }else{
		 	$raw = $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'];
		 	$sha1 = sha1($raw);
		 	return $sha1;
		 }			
	}

	public static function doCreateWordsRaw($data){

		if(!empty($data['device_id'])){

			if(!empty($data['pairing_code'])){				
				return $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'] . $data['token'] . $data['pairing_code'] . $data['device_id'];

			}else{

				return $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'] . $data['device_id'];

			}

		}else if(!empty($data['pairing_code'])){

			return $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'] . $data['token'] . $data['pairing_code'];

		}else if(!empty($data['currency'])){

			return $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'] . $data['currency'];

		}else{

			return $data['amount'] . DokuInitiate::$mallId . DokuInitiate::$sharedKey . $data['invoice'];

		}
	}

	public static function formatBasket($data){		
		
		$parseBasket = "";
		if(is_array($data)){
			foreach($data as $basket){
				$parseBasket = $parseBasket . $basket['name'] .','. $basket['amount'] .','. $basket['quantity'] .','. $basket['subtotal'] .';';
			}
		}else if(is_object($data)){
			foreach($data as $basket){
				$parseBasket = $parseBasket . $basket->name .','. $basket->amount .','. $basket->quantity .','. $basket->subtotal .';';
			}
		}else{
			$parseBasket = $data;
		}

		return $parseBasket;
	}

}
