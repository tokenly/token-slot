<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Payment, App\Models\Slot;
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
        $since = $this->option('since');
        $caller = app('Tokenly\XcallerClient\Client');
        if($since AND trim($since) != ''){
            $date = date('Y-m-d H:i:s', strtotime($since));
            $payment_list = Payment::where('init_date', '>=', $date)->get();
            if(!$payment_list){
                $payment_list = array();
            }
        }
        else{
            $getPayment = Payment::where('address', $id)->orWhere('id', $id)->first();
            if(!$getPayment){
                $this->error('Payment not found');
                return false;
            }            
            $payment_list = array($getPayment);
        }
        
        foreach($payment_list as $getPayment){
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
            if($getPayment->min_conf != null){
                $hookData['confirmations'] = $getPayment->min_conf;
            }			
            $hookData['init_date'] = $getPayment->init_date;
            $hookData['complete'] = $complete;
            $hookData['complete_date'] = $getPayment->complete_date;
            $hookData['tx_info'] = json_decode($getPayment->tx_info, true);
            
            try{
                $sendWebhook = $caller->sendWebhook($hookData, $getSlot->webhook);
            }
            catch(\Exception $e){
                $this->error('Error sending notification '.$e->getMessage());
                return false;
            }
            
            
            $this->info('Payment notification sent'.$complete_txt);
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
			array('since', null, InputOption::VALUE_REQUIRED, 'Ignore id and resend notifications for everything since X date')
		];
	}

}
