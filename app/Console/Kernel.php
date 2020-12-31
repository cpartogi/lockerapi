<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        /*Commands\Inspire::class,*/
        Commands\LockerStatus::class,
        Commands\DbaseBackup::class,
        Commands\SMSReminder::class,
    	Commands\ApacheBackup::class,
        Commands\ForceResync::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule){
        $schedule->command('lockerstatus')->cron('*/5 * * * *')->appendOutputTo('/home/developer/newlocker/storage/app/debugapp/lockerstatus.txt');
        $schedule->command('dbasebackup')->dailyAt('05:00');
        $schedule->command('smsreminder')->everyMinute()->appendOutputTo('/home/developer/newlocker/storage/app/debugapp/smsreminder.txt');
        $schedule->command('apachebackup')->dailyAt('00:00');
        $schedule->command('forceresync')->hourly();
    }
}
