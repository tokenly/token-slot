<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Payment;
use Tokenly\CurrencyLib\CurrencyUtil as Currency;

class markComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokenslot:markComplete {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks a payment request complete and sends notifications';

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
    public function handle()
    {
        $address = $this->argument('address');
        $getPayment = Payment::where('address', $address)->first();
        if(!$getPayment){
            $this->error('Payment not found');
            return false;
        }
        $getSlot = $getPayment->slot();
        $getPayment->complete = 1;
        $save = $getPayment->save();
        if(!$save){
            $this->error('Error saving payment');
            return false;
        }
        $this->info('Invoice marked complete');
        if(trim($getSlot->webhook) != ''){ //only send notification if they have an actual webhook set
            //send off a notification to the clients webhook
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
            $hookData['complete'] = boolval($getPayment->complete);
            $hookData['complete_date'] = $getPayment->complete_date;
            $hookData['tx_info'] = json_decode($getPayment->tx_info, true);
            
            $sendWebhook = $caller->sendWebhook($hookData, $getSlot->webhook);
            $this->info('Webhook notification sent');
        }
    }
}
