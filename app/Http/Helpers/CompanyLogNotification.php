<?php namespace App\Http\Helpers;

use Illuminate\Support\Facades\DB;
use App\CompanyNotification;

class CompanyLogNotification {
	
	var $companyId, $activityId, $status;
	
	function __construct($companyId, $activityId, $status) {
		$this->companyId = $companyId;
		$this->activityId = $activityId;
		$this->status = $status;
	}
	
	public function save() {
		$comp = new CompanyNotification();
		$comp->id_company = $this->companyId;
		$comp->id_locker_activities = $this->activityId;
		$comp->notification_status = $this->status;
		$comp->notification_date = date("Y-m-d H:i:s");
		$comp->save();
	}
	
}
