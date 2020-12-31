<?php

namespace App\Console\Commands;

use App\Http\Requests;
use App\Http\Helpers\WebCurl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator, Input, Redirect ; 


class ForceResync extends Command
{

    var $curl;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forceresync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual Task to Force Resync Data to PopBox';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
        $headers = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    // function from old app
    protected $headers = ['Content-Type: application/json'];

    public function handle(){
        echo "BEGIN CHECK AT ".date('Y-m-d H:i:s')."\n";
        $notSynced = DB::select("SELECT id_response as no, api_url as url, api_send_data as send_data FROM newlocker_db.tb_newlocker_generallog where api_url like '%internalapi.clientname.id/synclocker%' and (api_response = 'null' or api_response like '%headers%') ORDER BY id_response ASC");

        if (count($notSynced) != 0) {
            for($i = 0; $i < count($notSynced); ++$i) {  
                $data = $notSynced[$i];
                $send_data = $data->send_data;
                $url = $data->url; 
                $no = $data->no;
                // var_dump(json_encode($send_data));
                $curl = new WebCurl();
                $response = $curl->post($url, $send_data, $this->headers);
                if(!empty($response)){
                    DB::table('tb_newlocker_generallog')->where('id_response', $no)->update([
                        'api_response' => $response,
                        'response_date' => date("Y-m-d H:i:s")
                    ]);
                }
                echo "Sync $no : $response\n";
                sleep(1.5);         
            }
        } 
        echo "=> ".count($notSynced). " RECORDS FOUND & RESYNCED============\n";
    }
}
