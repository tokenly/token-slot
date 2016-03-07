<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Payment, Slot;
use Tokenly\CurrencyLib\CurrencyUtil as Currency;

class resendNotification extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'resendNotification';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resends a payment notification to webhook';

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
		$id = $this->argument('id');
		$getPayment = Payment::where('address', $id)->orWhere('id', $id)->first();
		if(!$getPayment){
			$this->error('Payment not found');
			return false;
		}
		$getSlot = Slot::find($getPayment->slotId);
		if(trim($getSlot->webhook) == ''){
			$this->error('No webhook for slot '.$getSlot->id);
			return false;
		}
		
		$complete = boolval($getPayment->complete);
		$complete_txt = '';
		if(boolval($this->argument('complete'))){
			$complete = true;
			$getPayment->complete = 1;
			$getPayment->save();
			$complete_txt = ' (completed)';
		}
		
		$caller = app('Tokenly\XcallerClient\Client');
		$hookData = array();
		$hookData['payment_id'] = $getPayment->id;
		$hookData['slot_id'] = $getSlot->public_id;
		$hookData['reference'] = $getPayment->reference;
		$hookData['payment_address'] = $getPayment->address;
		$hookData['asset'] = $getPayment->token;
		$hookData['total'] = Currency::satoshisToValue($getPayment->total);
		$hookData['total_satoshis'] = $getPayment->total;
		$hookData['received'] = Currency::satoshisToValue($getPayment->received);
		$hookData['received_satoshis'] = $getPayment->received;
		$hookData['confirmations'] = $getSlot->min_conf; 				
		$hookData['init_date'] = $getPayment->init_date;
		$hookData['complete'] = $complete;
		$hookData['complete_date'] = $getPayment->complete_date;
		$hookData['tx_info'] = json_decode($getPayment->tx_info, true);
		
		$sendWebhook = $caller->sendWebhook($hookData, $getSlot->webhook);
		
		if(!$sendWebhook){
			$this->error('Error sending notification');
			return false;
		}
		
		$this->info('Payment notification sent'.$complete_txt);
		
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['id', InputArgument::REQUIRED, 'Payment ID or address'],
			['complete', InputArgument::OPTIONAL, 'Set request to complete or not', false]
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
