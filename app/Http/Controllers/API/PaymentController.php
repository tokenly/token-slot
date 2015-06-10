<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use User, Slot, Input, Response, Payment;

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
		
		$getSlot->tokens = json_decode($getSlot->tokens, true);
		if(!is_array($getSlot->tokens)){
            $message = "Slot accepted token list invalid";
			return Response::json(array('error' => $message), 400);
		}
		
		if(!isset($input['token']) OR !in_array(strtoupper(trim($input['token'])), $getSlot->tokens)){
            $message = "Token ".$input['token']." not accepted by this slot";
			return Response::json(array('error' => $message), 400);	
		}
		$input['token'] = strtoupper(trim($input['token']));

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
		
		$payments = $payments->select('id', 'address', 'token', 'total', 'received', 'complete', 'init_date', 'complete_date',
							 'reference', 'tx_info', 'slotId as slot_id', 'cancelled', 'cancel_time')->get();
					
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
		}
		return Response::json($payments);
	}	
}
