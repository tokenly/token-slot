<?php namespace App\Console\Commands;

use User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use LinusU\Bitcoin\AddressValidator;
use \Exception;

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
		
		if(!AddressValidator::isValid($address)){
			throw new Exception('Invalid BTC address');
		}
	
		$user = new User;
		$user->api_key = User::generateKey($email);
		$user->email = $email;
		$user->forward_address = $address;
		$user->activated = 1;

		// try to save the user, but catch a duplicate email
		try {
			$save = $user->save();
        } catch (QueryException $e) {
            if ($e->errorInfo[0] == 23000) { throw new Exception('Client already exists'); }
            throw $e;
        }

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
