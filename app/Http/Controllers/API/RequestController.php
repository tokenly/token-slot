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
		
		/*
		 * come back to this and add code to obtain address and validate request total
		 * */

		$ref = '';
		if(isset($input['ref'])){
			$ref = trim($input['ref']);
		}
		
		//save the payment data
		$payment = new Payment;
		$payment->slotId = $getSlot->id;
		$payment->address = ''; //<- add address from xchain
		$payment->total = 0; //total in satoshis
		$payment->init_date = timestamp();
		$payment->IP = $_SERVER['REMOTE_ADDR'];
		$payment->reference = $ref; //user assigned reference
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
