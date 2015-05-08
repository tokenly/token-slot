<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use \Exception;
use AddressValidate, User;

class generateClient extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'generateClient';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generates a new client in the system';

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
		$address = trim($this->argument('address'));
		
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			throw new Exception('Invalid email address');
		}
		
		$validate = new AddressValidate;
		if(!$validate->checkAddress($address)){
			throw new Exception('Invalid BTC address');
		}
		
		$checkExists = User::where('email', '=', $email)->get();
		if($checkExists){
			throw new Exception('Client already exists');
		}
		
		$user = new User;
		$user->api_key = User::generateKey($email);
		$user->email = $email;
		$user->forward_address = $address;
		$user->activated = 1;
		$save = $user->save();
		if(!$save){
			throw new Exception('Error saving new client');
		}
		$this->info('Client ['.$email.'] created. API KEY: '.$user->api_key."\n");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['email', InputArgument::REQUIRED, 'Client\'s email address'],
			['address', InputArgument::REQUIRED, 'Backup forwarding address for client account'],
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
