<?php namespace App\Http\Helpers;

use Illuminate\Support\Facades\DB;
use App\CompanyResponse;

class CompanyLogResponse {
	
	var $url, $sendData, $response;
	
	function __construct($url, $sendData, $response) {
		$this->url = $url;
		$this->sendData = is_array($sendData) ? json_encode($sendData) : $sendData;
		$this->response = is_array($response) ? json_encode($response) : $response;
	}
	
	public function save() {
		$compResp = new CompanyResponse();
		$compResp->api_url = $this->url;
		$compResp->api_send_data = $this->sendData;
		$compResp->api_response = $this->response;
		$compResp->response_date = date("Y-m-d H:i:s");
		$compResp->save();
	}
	
}
