<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use \Exception, User;

class toggleClient extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'toggleClient';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Switches a client account from active to inactive state';

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
	public function fire()
	{
		$email = trim($this->argument('email'));
		$state = intval($this->argument('state'));
		if($state != 0){
			$state = 1;
		}
		$find = User::where('email', '=', $email)->first();
		if(!$find){
			throw new Exception('Client not found');
		}
		$find->activated = $state;
		$find->save();
		switch($state){
			case 0:
				$this->info('Client ['.$email.'] deactivated');
				break;
			case 1:
				$this->info('Client ['.$email.'] activated');
				break;
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['email', InputArgument::REQUIRED, 'Client email'],
			['state', InputArgument::OPTIONAL, '1 to activate, 0 to deactive',1],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
		];
	}

}
