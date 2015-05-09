<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use User, Slot, Input, Response;

class RequestController extends APIController {
	
	/**
	 * initiate a payment request
	 * @param string $slotId the public_id of the payment "slot"
	 * @return Response
	 * */
	public function get($slotId)
	{
		$user = User::$api_user;
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
		 * - generate a fresh address
		 * - create a fresh transaction monitor with xchain and define webhook
		 * - setup payment in DB and save details
		 * - return address + payment ID etc.
		 * 
		 * */
		
		return Response::json($output);
	}
}
