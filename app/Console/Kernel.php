<?php

namespace App\Console;

use App\Http\Controllers\GameStatisticsController;
use App\Scheduling;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Mockery\Expectation;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use ZipArchive;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // dev
        $schedule->call(function () {

        })
        ->dailyAt('01:30');

        $schedule->call(function () {

        })->dailyAt('01:30');


        $schedule->call(function () {


        })->monthly();
        
        $schedule->call(function () {
        })->everyMinute();
        
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
