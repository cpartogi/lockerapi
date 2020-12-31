<?php

namespace App\Http\Helpers;

class ArrayCheckHelper {
	
	private $array;
	
	public function ArrayCheck($array) {
		$this->array = $array;
	}
	
	public function get($paramName) {
		print_r($this->array);
		if(is_array($this->array) && isset($this->array[$paramName]) && $this->array[$paramName] != null)
			return $this->array[$paramName];
		
		return '';
	}
	
}
