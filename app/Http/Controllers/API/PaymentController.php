<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use User, Slot, Input, Response, Payment, URL;

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
		$getSlot = Slot::where('public_id', '=', $slotId)->first();
		if(!$getSlot){
            $message = "Invalid slot ID";
			return Response::json(array('error' => $message), 400);
		}
		
		$slotUser = User::find($getSlot->userId);
		if($slotUser->id != $user->id){
            $message = "Invalid permission for slot";
			return Response::json(array('error' => $message), 403);
		}

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
		$valid_slots = array();
		foreach($slots as $slot){
			$valid_slots[] = $slot->id;
		}
		
		$getPayment = Payment::whereIn('slotId', $valid_slots)
					  ->where(function($query) use($paymentId){
						  return $query->where('id', '=', $paymentId)
										->orWhere('address', '=', $paymentId)
										->orWhere('reference', '=', $paymentId);
						  
					  })
					  ->select('id', 'slotId', 'address', 'total', 'received',
							   'complete', 'init_date', 'complete_date',
							   'tx_info', 'reference')
					  ->first();
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
		
		return Response::json($getPayment);
	}
}
