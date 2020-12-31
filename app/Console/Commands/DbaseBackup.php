<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mail;


class DbaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbasebackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'backup/dump mysql database';

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

    
    public function handle()
    {
    
           exec("mysqldump -u newlocker -pVHyAVWuWwWPZ9cyaF9Av --events --ignore-table=mysql.event --all-databases | gzip > /home/developer/newlocker/storage/dbackup/newlockerdb_`date '+%Y-%m-%d'`.sql.gz");
	  // hapus file yang usianya seminggu
      $tanggal = date('Y.m.d', strtotime('-7 days'));
      $tanggal2 = date('Y-m-d', strtotime('-7 days'));
      $tanggal3 = date('Ymd', strtotime('-7 days'));
      $backupfile = "newlockerdb_".$tanggal2.".sql.gz";;
    //  echo "file backup seminggu yg lalu = ".$backupfile;
      
      if (file_exists("/home/developer/newlocker/storage/dbackup/$backupfile")) {
        exec("rm /home/developer/newlocker/storage/dbackup/$backupfile");
      //        echo "file found : /home/developer/getdata/storage/logs/dbackup/".$backupfile;
      }
      
        

      	
  	}	        
}
