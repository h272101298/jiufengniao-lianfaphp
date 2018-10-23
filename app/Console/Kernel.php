<?php

namespace App\Console;

use App\Console\Commands\Brokerage;
use App\Console\Commands\checkGroupBuy;
use App\Console\Commands\ClearQueue;
use App\Console\Commands\Notify;
use App\Console\Commands\refuseOrder;
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
        //
        Brokerage::class,
        Notify::class,
        ClearQueue::class,
        checkGroupBuy::class,
        refuseOrder::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('brokerage:make')->dailyAt('2:00');
        $schedule->command('notify:send')->everyFiveMinutes();
        $schedule->command('clearQueues')->everyFiveMinutes();
        $schedule->command('checkGroupBuy')->everyMinute();
        $schedule->command('refuseOrder')->everyFiveMinutes();
        $schedule->command('receiveOrder')->dailyAt('3:00');
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
