<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use LinusU\Bitcoin\AddressValidator;
use \Exception, User, Slot;

class createSlot extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'createSlot';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new token slot for the client';

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
		$email = $this->argument('email');
		$user = User::where('email', '=', $email)->first();
		if(!$user){
			throw new Exception('Client not found');
		}
		
		$asset = strtoupper($this->argument('asset'));
		$webhook = $this->argument('webhook');
		$address = $this->argument('address');
		
		$xchain = xchain(); //check if asset is real
		try{
			$checkAsset = $xchain->getAsset($asset);
		}
		catch(Exception $e){
			throw new Exception('Invalid asset');
		}

		if(trim($address) != ''){
			if(!AddressValidator::isValid($address)){
				throw new Exception('Invalid BTC forwarding address');
			}
		}
		
		$slot = new Slot;
		$slot->userId = $user->id;
		$slot->public_id = str_random(20);
		$slot->asset = $asset;
		$slot->webhook = $webhook;
		$slot->min_conf = intval($this->argument('min_conf'));
		$slot->forward_address = $address;
		$slot->label = $this->argument('label');
		$slot->nickname = $this->argument('nickname');
		$save = $slot->save();
		$this->info($asset.' token slot created with public ID '.$slot->public_id."\n");
	}

	/**
	 * Get the console command arguments.
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['email', InputArgument::REQUIRED, 'Client email'],
			['asset', InputArgument::REQUIRED, 'Asset/Token to accept'],
			['webhook', InputArgument::OPTIONAL, 'Webhook to send payment data to'],
			['label', InputArgument::OPTIONAL, 'Optional reference label',null],
			['min_conf', InputArgument::OPTIONAL, 'Minimum confirmations required for payment completion',1],
			['address', InputArgument::OPTIONAL, 'Specific forwarding address',null],
			['nickname', InputArgument::OPTIONAL, 'Nickname/alias for the slot, can be used instead of public_id',null],
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
