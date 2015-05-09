<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use User, Slot, Input, Response, Payment;

class RequestController extends APIController {
	
	/**
	 * initiate a payment request
	 * @param string $slotId the public_id of the payment "slot"
	 * @return Response
	 * */
	public function get($slotId)
	{
		$user = User::$api_user;
		$input = Input::all();
		$output = array();
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
			$monitor = $xchain->newAddressMonitor($address['address'], $getSlot->webhook);
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
		if(isset($input['ref'])){ //user assigned reference
			$ref = trim($input['ref']);
		}
		
		//save the payment data
		$payment = new Payment;
		$payment->slotId = $getSlot->id;
		$payment->address = $address['address']; 
		$payment->total = $total;
		$payment->init_date = timestamp();
		$payment->IP = $_SERVER['REMOTE_ADDR'];
		$payment->reference = $ref; 
		$payment->payment_uuid = $address['id'];
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
}
