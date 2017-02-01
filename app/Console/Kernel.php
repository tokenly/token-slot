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
		'App\Console\Commands\expirePayments',
		'App\Console\Commands\markComplete',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('sweepTokens')->hourly()->withoutOverlapping()->sendOutputTo(storage_path().'/sweep.log');
		$schedule->command('expirePayments --archive-on-error=1')->hourly()->withoutOverlapping()->sendOutputTo(storage_path().'/expire.log');
	}

}
