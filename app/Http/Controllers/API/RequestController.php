<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User, Slot, Input;

class RequestController extends APIController {
	
	/**
	 * initiate a payment request
	 * @param string $slotId the public_id of the payment "slot"
	 * @return Response
	 * */
	public function get($slotId)
	{
		$output = array();
		//check if this is a legit slot
		$getSlot = Slot::where('public_id', '=', $slotId)->first();
		if(!$getSlot){
            $message = "Invalid slot ID $slotId";
            EventLog::logError('error.getSlot', ['slotId' => $slotId, 'message' => $message]);
            return new JsonResponse(['message' => $message], 400); 
		}
		//make sure the user is real and activated
		$slotUser = User::find($getSlot->userId);
		if(!$slotUser OR $slotUser->activated == 0){
            $message = "Account not activated for slot $slotId";
            EventLog::logError('error.inactiveUser', ['slotId' => $slotId, 'message' => $message]);
            return new JsonResponse(['message' => $message], 403); 
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
		
		return json_encode($output);
	}
}
