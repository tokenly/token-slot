<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class newAddress extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'newAddress';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generates a new address from XChain';

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
		$xchain = xchain();
		$get = $xchain->newPaymentAddress();
		if($get){
			$this->info("Address Created: \n ".$get['id']." \n ".$get['address']."\n");
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
