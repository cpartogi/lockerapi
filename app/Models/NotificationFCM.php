<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationFCM extends Model
{
    // set table
    protected $table = 'tb_notification_fcm';

    /**
     * insert notification
     * @param null $module module who access the fcm notification
     * @param array $to GCM token
     * @param string $type device type
     * @param string $title
     * @param string $body
     * @param array $data
     * @return \stdClass
     */
    public static function insertNotification($module=null,$to=[],$type='android',$title,$body,$data=[]){
        // generate response
        $response = new \stdClass();
        $response->isSuccess = false;
        $response->errorMsg = null;

        if (empty($data)){
            $data=[];
            $data['link'] = '';
            $data['image'] = '';
            $data['image2'] = '';
        }

        foreach ($to as $item){
            $db = new self();
            $db->module = $module;
            $db->to = $item;
            $db->type = $type;
            $db->title = $title;
            $db->body = $body;
            if (!is_string($data)) $data = json_encode($data);
            $db->data = $data;
            $db->save();
        }
        $response->isSuccess = true;
        return $response;
    }
}
