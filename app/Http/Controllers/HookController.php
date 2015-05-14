<?php namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Tokenly\CurrencyLib\CurrencyUtil as Currency;
use Config, Input, Slot, Payment;
class HookController extends Controller {

	/**
	 * webhook for receiving payment notifications from xchain
	 * @return null
	 */
	public function payment(Request $request)
	{	 
		$hook = app('Tokenly\XChainClient\WebHookReceiver');
		$xchain = xchain();
		$parseHook = $hook->validateAndParseWebhookNotificationFromRequest($request);
		$input = $parseHook['payload'];
		 if(is_array($input) AND isset($input['notifiedAddress']) AND Input::get('nonce')){
			 //check payment with this address exists
			 $getPayment = Payment::where('address', '=', $input['notifiedAddress'])->where('complete', '=', 0)->first();
			 if($getPayment){
				 //check against the secret nonce
				 $generateNonce = strtotime($getPayment->init_date).$getPayment->slotId;
				 if($generateNonce == intval(Input::get('nonce'))){
					 //check for proper asset
					 $getSlot = Slot::find($getPayment->slotId);
					 if($getSlot->asset == $input['asset']){
						 $tx_info = json_decode($getPayment->tx_info, true);
						 $found = false;
						 if(is_array($tx_info)){
							 //check if transaction is seen already, if so update its confirmation account
							 foreach($tx_info as &$info){
								 if($info['txid'] == $input['txid']){
									 $found = true;
									 $info['confirmations'] = $input['confirmations'];
								 }
							 }
						 }
						 else{
							 $tx_info = array();
						 }
						 if(!$found){
							//add transaction to tx_info field
							 $tx_info[] = array('sources' => $input['sources'], 'txid' => $input['txid'],
												'amount' => $input['quantitySat'], 'confirmations' => $input['confirmations']);
												
						 }
						 //get a total amount received and check if payment is complete
						 $totalReceived = 0;
						 $leastConfirmed = 0;
						 $complete = false;
						 if(count($tx_info) > 0){
							 $complete = true;
							 foreach($tx_info as $info){
								 $totalReceived += $info['amount'];
								 if($info['confirmations'] < $getSlot->min_conf){
									 $complete = false; //one of the transactions has less than required confirms, not complete
								 }
								 if($info['confirmations'] > $leastConfirmed){
									 $leastConfirmed = $info['confirmations'];
								 }
							 }
						}
						if($totalReceived < $getPayment->total){
							$complete = false;
						}
						
						if($complete){
							//payment is complete.. mark it as such
							$getPayment->complete = 1;
							$getPayment->complete_date = timestamp();
							
							//send a request to xchain to close the payment notifier
							$xchain->updateAddressMonitorActiveState($getPayment->monitor->uuid, false);
						}
						
						//save info
						$getPayment->received = $totalReceived;
						$getPayment->tx_info = json_encode($tx_info);
						$save = $getPayment->save();
						
						if($save){
							//send off a notification to the clients webhook
							$caller = app('Tokenly\XcallerClient\Client');
							$hookData = array();
							$hookData['payment_id'] = $getPayment->id;
							$hookData['slot_id'] = $getSlot->public_id;
							$hookData['reference'] = $getPayment->reference;
							$hookData['payment_address'] = $getPayment->address;
							$hookData['asset'] = $getSlot->asset;
							$hookData['total'] = Currency::satoshisToValue($getPayment->total);
							$hookData['total_satoshis'] = $getPayment->total;
							$hookData['received'] = Currency::satoshisToValue($totalReceived);
							$hookData['received_satoshis'] = $totalReceived;
							$hookData['confirmations'] = $leastConfirmed;							
							$hookData['init_date'] = $getPayment->init_date;
							$hookData['complete'] = boolval($getPayment->complete);
							$hookData['complete_date'] = $getPayment->complete_date;
							$hookData['tx_info'] = $tx_info;
							
							$sendWebhook - $caller->sendWebhook($hookData, $getSlot->webhook);
						}
					 }
				 }
				 
			 }
		 }
	}

}
