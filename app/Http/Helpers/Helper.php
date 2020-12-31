<?php
/**
 * Created by PhpStorm.
 * User: arief
 * Date: 17/01/2017
 * Time: 9:23
 */

namespace App\Http\Helpers;


class Helper
{
    /**
     * generate random string
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 10) {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function queueEmail($from='',$to='',$fileView){

    }

    /**
     * @param $message
     * @param string $folderName
     * @param string $filename
     * @param null $sessionId
     */
    public static function LogPayment($message,$folderName='',$filename='payment',$sessionId=null){
        $path = storage_path().'/logs/'.$folderName;
        if (!is_dir($path)){
            mkdir($path);
        }

        $message = "$sessionId > $message\n";
        $f = fopen($path.'/'.$filename.date('Y.m.d.').'log','a');
        fwrite($f,date('H:i:s')." $message");
        fclose($f);
    }
}