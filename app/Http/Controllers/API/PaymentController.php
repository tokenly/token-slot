<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use LinusU\Bitcoin\AddressValidator;
use User, App\Models\Slot, Input, Response, Payment, Config;

class PaymentController extends APIController {
	
	/**
	 * initiate a payment request
	 * @param string $slotId the public_id of the payment "slot"
	 * @return Response
	 * */
	public function request($slotId)
	{
		$user = User::$api_user;
		$input = Input::all();
		$output = array();
		$time = timestamp();
		//check if this is a legit slot
		$getSlot = Slot::where('userId', '=', $user->id)
						 ->where('public_id', '=', $slotId)
						 ->orWhere('nickname', '=', $slotId)
						 ->first();
		if(!$getSlot){
            $message = "Invalid slot ID";
			return Response::json(array('error' => $message), 400);
		}
        
		if(!isset($input['token'])){
            $message = "Payment token name required";
			return Response::json(array('error' => $message), 400);
		}        
        $input['token'] = strtoupper(trim($input['token']));
		
		$getSlot->tokens = json_decode($getSlot->tokens, true);
		if(is_array($getSlot->tokens) AND count($getSlot->tokens) > 0){
            if(!in_array($input['token'], $getSlot->tokens)){
                $message = "Token ".$input['token']." not accepted by this slot";
                return Response::json(array('error' => $message), 400);	
            }            
		}

		
		/*** Begin pegging code ***/
		//validators for peg options
		//start with the valid flags FALSE
		//we will validate if we receive these options in the input
		$valid_peg = FALSE;
		$valid_peg_total = FALSE;
		$valid_peg_calculated = FALSE;
		$peg = '';
		$peg_total = '';
		$peg_tokens_list = Config::get('settings.peggable_tokens');
		$peg_currencies = Config::get('settings.peg_currencies');
		$peg_currency_denoms = Config::get('settings.peg_currency_denoms');
		$peg_token_aliases = Config::get('settings.peggable_token_aliases');
		$SATOSHI_MOD = 100000000;
		
		if(isset($input['peg']) AND trim($input['peg']) != ''){
			$peg = strtoupper(trim($input['peg']));
			if(in_array($peg, $peg_currencies)){
				$valid_peg = TRUE;
			}
			else{
				$message = "Pegging API only supports ".join(', ',$peg_currencies).", ".$peg." is invalid";
				return Response::json(array('error' => $message), 400);
			}
			if(isset($input['peg_total'])){
				$peg_decimal = 100;
				if(isset($peg_currency_denoms[$peg])){
					$peg_decimal = $peg_currency_denoms[$peg];
				}
				$peg_total = intval($input['peg_total']) / $peg_decimal;
				if($peg_total > 0){
					$valid_peg_total = TRUE;                        
				}
			}		
		}
		if($valid_peg === TRUE AND $valid_peg_total === TRUE){
			//the list of tokens we can peg to USD
			
			$input_peg_token = $input['token'];
			if(isset($peg_token_aliases[$input_peg_token])){
				$input_peg_token = $peg_token_aliases[$input_peg_token];
			}

			//make sure it's a token we can peg
			if(!in_array($input_peg_token,$peg_tokens_list)){
				$message = 'Pegging not supported with '.$input_peg_token.'. Supported tokens: '.join(', ', $peg_tokens_list);
				return Response::json(array('error' => $message), 400);
			}

			$quotebot_url = env('QUOTEBOT_URL');

			//we pull real time price data from quotebot
			$quotebot_response = file($quotebot_url);
			$quotebot_json_data = json_decode($quotebot_response[0]);
			if(!is_object($quotebot_json_data)){
				$message = 'Error retrieving token price quotes';
				return Response::json(array('error' => $message), 400);
			}
			$quotes = $quotebot_json_data->{'quotes'};
			$pegged_satoshis = 0;
			$fiat_btc = 0;
			foreach($quotes as $quote){
				list($payment_currency,$order_currency) = explode(':',$quote->{'pair'});
				if($quote->{'source'} == 'bitcoinAverage' AND $order_currency == 'BTC' AND $payment_currency == $peg){
					$fiat_btc = $quote->{'last'};
				}				
				if($payment_currency == $peg AND $order_currency == $input_peg_token){
					//direct quote found
					$quote_price = $quote->{'last'};
					$pegged_satoshis = round(($peg_total / $quote_price), 4);
					$pegged_satoshis = intval($pegged_satoshis * $SATOSHI_MOD);
					break;
				}
			}

			if($pegged_satoshis == 0 AND $fiat_btc > 0){
				foreach($quotes as $quote){
					//now find the BTC price for our token
					list($payment_currency,$order_currency) = explode(':',$quote->{'pair'});
					if($order_currency == $input_peg_token){
						//find the BTC satoshis for our peg total
						$btc_satoshis = round(($peg_total/$fiat_btc) * $SATOSHI_MOD);
						if($input_peg_token == 'BTC'){
							$pegged_satoshis = $btc_satoshis;
						}
						else{
							$token_price_satoshis = $quote->{'last'};
							//finally, figure out satoshis of the token
							$pegged_satoshis =round(($btc_satoshis / $token_price_satoshis), 4);
							$pegged_satoshis = intval($pegged_satoshis * $SATOSHI_MOD);
						}
					}
				}
			}
	
			if($pegged_satoshis <= 0){
				$message = "Could not obtain peg for ".$peg.":".$input['token'];
				return Response::json(array('error' => $message), 400);
			}

			//this line feeds a value into the "total" processing code about 20 lines down
			$input['total'] = $pegged_satoshis;
		}
		elseif($valid_peg === TRUE AND $valid_peg_total === FALSE){
			$message = "Gave a valid peg, but peg_total ".$peg_total." is invalid";
			return Response::json(array('error' => $message), 400);
		}
		elseif($valid_peg === FALSE AND $valid_peg_total === TRUE){
			$message = "Gave a valid peg_total, but peg ".$peg." is invalid";
			return Response::json(array('error' => $message), 400);
		}
		else{
			//no peg options given, do nothing
		}
		if($peg_total > 0){
			//turn peg total number into an integer for DB storage
			$default_decimal = 100; //defaults to standard fiat cents
			if(isset($peg_currency_denoms[$peg])){
				$peg_total = round($peg_total * $peg_currency_denoms[$peg]);
			}
			else{
				$peg_total = round($peg_total * $default_decimal);
			}
		}
		/*** End pegging code ***/

		//initialize xchain client
		$xchain = xchain();
		try{
			$address = $xchain->newPaymentAddress();
			$monitor = $xchain->newAddressMonitor($address['address'], route('hooks.payment').'?nonce='.strtotime($time).$getSlot->id);
		}
		catch(Exception $e){
			return Response::json(array('error' => 'Error generating payment request'), 500);
		}
		
		$total = 0; //allow for 0 total for "pay what you want" type situations
		//the pegging code about 20 lines above feeds into here when valid peg input is provided
		//totals should be in satoshis (or just plain number if non-divisible asset)
		if(isset($input['total'])){
			$total = intval($input['total']);
			if($total < 0){
				return Response::json(array('error' => 'Invalid total'), 400);
			}
		}
		
		$ref = ''; 
		if(isset($input['reference'])){
			$input['ref'] = $input['reference'];
		}
		if(isset($input['ref'])){ //user assigned reference
			$ref = trim($input['ref']);
		}
		
		$forward_address = null;
		if(isset($input['forward_address'])){
			$forward_address = $input['forward_address'];
			if(!is_array($forward_address)){
				$attempt_decode = json_decode($forward_address, true);
				if(is_array($attempt_decode)){
					$forward_address = $attempt_decode;
				}
			}
			if(is_array($forward_address)){
				$forward_list = array();
				$used_split = 100;
				foreach($forward_address as $k => $r){
					$f_address = trim($k);
					if(!AddressValidator::isValid($f_address)){
						$output = array('error' => 'Invalid BTC address '.$f_address);
						return Response::json($output, 400);
					}
					$f_split = floatval($r);
					$used_split -= $f_split;
					if($used_split < 0 OR $used_split > 100){
						$output = array('error' => 'Invalid forwarding address split amounts, cannot split less than 0% or greater than 100%');
						return Response::json($output, 400);
					}
					$forward_list[$f_address] = $f_split;
				}
				if($used_split > 0){
					//add remainder to top address
					$top_address = false;
					$top_split = false;
					foreach($forward_list as $f_address => $f_split){
						if(!$top_split){
							$top_split = $f_split;
							$top_address = $f_address;
						}
						else{
							if($f_split > $top_split){
								$top_split = $f_split;
								$top_address = $f_address;
							}
						}
					}
					if($top_address){
						$forward_list[$top_address] += $used_split;
					}
				}				
				$forward_address = json_encode($forward_list);
			}
			else{
				if(trim($forward_address) != ''){
					if(!AddressValidator::isValid($forward_address)){
						$output = array('error' => 'Invalid BTC address '.$forward_address);
						return Response::json($output, 400);
					}
				}	
			}	
		}		
        
        //custom min_conf for this invoice
        $min_conf = null; //null = use slot default
        if(isset($input['min_conf'])){
            $min_conf = intval($input['min_conf']);
            if($min_conf < 0){
                $output = array('error' => 'Invalid min_conf '.$min_conf);
                return Response::json($output, 400);
            }
        }
		
		//save the payment data
		$payment = new Payment;
		$payment->slotId = $getSlot->id;
		$payment->address = $address['address']; 
		$payment->token = $input['token'];
		$payment->total = $total;
		$payment->init_date = $time;
		$payment->IP = $_SERVER['REMOTE_ADDR'];
		$payment->reference = substr($ref, 0, 64);  //limit to 64 characters
		$payment->payment_uuid = $address['id']; //xchain references
		$payment->monitor_uuid = $monitor['id'];
		$payment->peg = $peg;
		$payment->peg_value = $peg_total;
		$payment->forward_address = $forward_address;
        $payment->min_conf = $min_conf;
		try{
			$save = $payment->save();
		}
		catch(Exception $e){
            $message = "Failed to create payment request";
			return Response::json(array('error' => $message), 500);
		}
		
		//setup the response
		$output['payment_id'] = $payment->id;
		$output['address'] = $payment->address;
		//optional code to provide the pegged tokens if valid peg input was given
		if($valid_peg === TRUE AND $valid_peg_total === TRUE){
		   $output['total'] = $total;
		   $output['peg'] = $peg;
		   $output['peg_value'] = $peg_total;
		}

		
		return Response::json($output);
	}
	
	/*
	 * gets data for a specific payment request
	 * @param mixed $paymentId the ID, "reference" or bitcoin address of a payment_request
	 * @return Response
	 * */
	public function get($paymentId)
	{
		$user = User::$api_user;
		$slots = Slot::where('userId', '=', $user->id)->get();

		$getPayment = Payment::getPayment($paymentId);
		if(!$getPayment){
            $message = "Invalid payment ID";
			return Response::json(array('error' => $message), 400);
		}
		
		$thisSlot = false;
		foreach($slots as $s){
			if($s->id == $getPayment->slotId){
				$thisSlot = $s;
				break;
			}
		}
		
		$getPayment->id = intval($getPayment->id);
		unset($getPayment->slotId);
		$getPayment->slot_id = $thisSlot->public_id;
		$getPayment->total = intval($getPayment->total);
		$getPayment->received = intval($getPayment->received);
		$getPayment->complete = boolval($getPayment->complete);
		$getPayment->tx_info = json_decode($getPayment->tx_info);
		$getPayment->cancelled = boolval($getPayment->cancelled);
		$getPayment->sweep_info = json_decode($getPayment->sweep_info);
		
		$decode_forward = json_decode($getPayment->forward_address, true);
		if(is_array($decode_forward)){
			$getPayment->forward_address = $decode_forward;
		}				
		
		return Response::json($getPayment);
	}
	
	/**
	 * cancels a payment request and sets the xchain address monitor to inactive.
	 * @param mixed $paymentId the ID, "reference" or bitcoin address of a payment_request
	 * @return Response
	 * */
	public function cancel($paymentId)
	{
		$output = array('result' => false);
		$getPayment = Payment::getPayment($paymentId);
		if($getPayment->cancelled == 1){
			$output['error'] = 'Payment already cancelled';
			return Response::json($output, 400);
		}
		$xchain = xchain();
		try{
			$xchain->updateAddressMonitorActiveState($getPayment->monitor_uuid, false);
		}
		catch(\Exception $e){
			$output['error'] = 'Error canceling payment request';
			return Response::json($output, 500);
		}
		$getPayment->cancelled = 1;
		$getPayment->cancel_time = timestamp();
		$getPayment->save();
		$output['result'] = true;
		return Response::json($output);
	}
	
	/**
	 * returns a list of all payment requests tied to this clients account
	 * @return Response
	 * */
	public function all()
	{
		$output = array();
		$user = User::$api_user;
		$input = Input::all();
		$slots = Slot::where('userId', '=', $user->id)->get();
		$valid_slots = array();
		if($slots){
			foreach($slots as $slot){
				$valid_slots[] = $slot->id;
			}
		}
		
		if(count($valid_slots) == 0){
			$output = array('error' => 'Please create a slot first');
			return Response::json($output, 400);
		}
		$payments = Payment::whereIn('slotId', $valid_slots);
		if(isset($input['incomplete'])){
			if(boolval($input['incomplete'])){
				$andComplete = true;
			}
			else{
				$andComplete = false;
			}
			$payments = $payments->where('complete', '=', $andComplete);
		}
		$andCancel = false;
		if(isset($input['cancelled'])){
			if(boolval($input['cancelled'])){
				$andCancel = true;
			}
		}
		if(!$andCancel){
			$payments = $payments->where('cancelled', '!=', '1');
		}
		
		$payments = $payments->select('id', 'address', 'token', 'total', 'received', 'peg', 'peg_value', 'complete', 'init_date', 'complete_date',
							 'reference', 'tx_info', 'slotId as slot_id', 'cancelled', 'cancel_time')->orderBy('id', 'desc')->get();
					
		foreach($payments as &$payment){
			$payment->tx_info = json_decode($payment->tx_info);
			$payment->total = intval($payment->total);
			$payment->received = intval($payment->received);
			$payment->complete = boolval($payment->complete);
			$payment->id = intval($payment->id);
			$payment->cancelled = boolval($payment->cancelled);
			foreach($slots as $slot){
				if($slot->id == $payment->slot_id){
					$payment->slot_id = $slot->public_id;
				}
			}
			$decode_forward = json_decode($payment->forward_address, true);
			if(is_array($decode_forward)){
				$payment->forward_address = $decode_forward;
			}				
		}
		return Response::json($payments);
	}	
}

