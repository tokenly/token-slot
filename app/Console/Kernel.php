<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\generateClient',
		'App\Console\Commands\toggleClient',
		'App\Console\Commands\createSlot',
		'App\Console\Commands\sweepTokens',
		'App\Console\Commands\newAddress',
		'App\Console\Commands\resendNotification',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('sweepTokens')->twiceDaily()->withoutOverlapping()->sendOutputTo(storage_path().'/sweep.log');
	}

}
