<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mail;


class ApacheBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apachebackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'backup and reset apache log';

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

		// tentukan file backup yang usianya 3 hari
    	$tanggal = date('Y-m-d', strtotime('-3 days'));
      	$backupfile = "access_".$tanggal.".log";

    	// backup log apache logistic
   		exec("cp /var/log/apache2/logistic/access.log /home/developer/logistic/storage/logs/apache/");
   		exec("mv /home/developer/logistic/storage/logs/apache/access.log /home/developer/logistic/storage/logs/apache/access_`date '+%Y-%m-%d'`.log");
   		exec("> /var/log/apache2/logistic/access.log"); 
      	if (file_exists("/home/developer/logistic/storage/logs/apache/$backupfile")) {
         	exec("rm /home/developer/logistic/storage/logs/apache/$backupfile");
      	}
         
        // backup log apache logistic api
   		exec("cp /var/log/apache2/logisticapi/access.log /home/developer/logisticapi/storage/logs/apache/");
   		exec("mv /home/developer/logisticapi/storage/logs/apache/access.log /home/developer/logisticapi/storage/logs/apache/access_`date '+%Y-%m-%d'`.log");
   		exec("> /var/log/apache2/agent/access.log"); 
      	if (file_exists("/home/developer/logisticapi/storage/logs/apache/$backupfile")) {
         	exec("rm /home/developer/logisticapi/storage/logs/apache/$backupfile");
      	} 
         
       	// backup log apache payment	
       	exec("cp /var/log/apache2/payment/access.log /home/developer/payment/storage/logs/apache/");
   		exec("mv /home/developer/payment/storage/logs/apache/access.log /home/developer/payment/storage/logs/apache/access_`date '+%Y-%m-%d'`.log");
   		exec("> /var/log/apache2/payment/access.log");    
      	if (file_exists("/home/developer/payment/storage/logs/apache/$backupfile")) {
         	exec("rm /home/developer/payment/storage/logs/apache/$backupfile");
      	}    
      	
      	// backup log apache newlocker	
       	exec("cp /var/log/apache2/newlocker/access.log /home/developer/newlocker/storage/logs/apache/");
   		exec("mv /home/developer/newlocker/storage/logs/apache/access.log /home/developer/newlocker/storage/logs/apache/access_`date '+%Y-%m-%d'`.log");
   		exec("> /var/log/apache2/newlocker/access.log");    
      	if (file_exists("/home/developer/newlocker/storage/logs/apache/$backupfile")) {
         	exec("rm /home/developer/newlocker/storage/logs/apache/$backupfile");
      	}    
      	
      	// backup log apache report	
       	exec("cp /var/log/apache2/report/access.log /home/developer/report/storage/logs/apache/");
   		exec("mv /home/developer/report/storage/logs/apache/access.log /home/developer/report/storage/logs/apache/access_`date '+%Y-%m-%d'`.log");
   		exec("> /var/log/apache2/report/access.log");    
      	if (file_exists("/home/developer/report/storage/logs/apache/$backupfile")) {
         	exec("rm /home/developer/report/storage/logs/apache/$backupfile");
      	}    
      	
      	// backup apache setting
      	exec("cp /etc/apache2/sites-enabled/* /home/developer/apacheconfig/");
      	
      	// backup ssl certificate
      	exec("cp /usr/local/ssl/private/* /home/developer/sslcertificate/key/");
      	exec("cp /usr/local/ssl/crt/* /home/developer/sslcertificate/crt/");   	
  	}	        
}
