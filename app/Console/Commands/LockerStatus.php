<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LockerStatus extends Command
{

    var $curl;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lockerstatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create schedule task for set status locker to 0';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */


    // function from old app
    protected $headers = array (
        'Content-Type: application/json'
    );
    protected $is_post = 0;

    public function handle(){
        $rangeTime = time()-300; //where no update for 5 minutes
        $query = "SELECT * FROM tb_newlocker_machinestat WHERE locker_name not LIKE '%TEST%' AND unix_timestamp(update_time) < ".$rangeTime;
        $offlines = DB::select($query);
        //dd($offlines);
        echo "=== CHECK OFFLINE on ".date("Y-m-d H:i:s").":\n";

        if (!empty($offlines)) {
            // update status locker menjadi 0
            foreach ($offlines as $off => $value) {
                $locker_name = $value->locker_name;
                DB::table('tb_newlocker_machinestat')
                    ->where('locker_name', $locker_name)
                        ->update(array('conn_status' => 0 ));
                echo "- ".$locker_name."\n";
            }
        }
        echo "=== ".count($offlines)." OFFLINE LOCKERS WAS FOUND!\n";
    }
}
