<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use User, Slot, Input, Response, Payment;

class SlotsController extends APIController {
	
	
	/**
	 * gives a list of all slots in the users' account
	 * @return Response
	 * */
	public function all()
	{
		$user = User::$api_user;
		$slots = Slot::where('userId', '=', $user->id)
				->select('public_id','asset','webhook','min_conf','forward_address',
						'label', 'nickname', 'created_at', 'updated_at')
				->get();
		foreach($slots as &$slot){
			$slot->min_conf = intval($slot->min_conf);
		}
		return Response::json($slots);
	}
	
	/**
	 * get data on a specific slot
	 * @param string $slotId the public_id of the slot, must belong to the user
	 * @return Response
	 * */
	public function get($slotId)
	{
		$user = User::$api_user;
		$slot = Slot::where('userId', '=', $user->id)
				->where('public_id', '=', $slotId)
				->orWhere('nickname', '=', $slotId)
				->select('public_id','asset','webhook','min_conf','forward_address',
						 'label', 'nickname', 'created_at', 'updated_at')
				->first();
		if(!$slot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$slot->min_conf = intval($slot->min_conf);
		return Response::json($slot);
	}
	
	/**
	 * get a list of payments made on a slot
	 * @param string $slotId the public_id of the slot, must belong to the user
	 * @return Response
	 * */
	public function payments($slotId)
	{
		$user = User::$api_user;
		$slot = Slot::where('userId', '=', $user->id)
					  ->where('public_id', '=', $slotId)
					  ->orWhere('nickname', '=', $slotId)
					  ->first();
		if(!$slot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$payments = Payment::where('slotId', '=', $slot->id)
					->select('id', 'address', 'total', 'received', 'complete', 'init_date', 'complete_date',
							 'reference', 'tx_info')
					->get();
		foreach($payments as &$payment){
			$payment->tx_info = json_decode($payment->tx_info);
			$payment->total = intval($payment->total);
			$payment->received = intval($payment->received);
			$payment->complete = boolval($payment->complete);
			$payment->id = intval($payment->id);
		}
		return Response::json($payments);
	}		
}
