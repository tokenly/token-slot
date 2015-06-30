<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use LinusU\Bitcoin\AddressValidator;
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
				->select('public_id','tokens','webhook','min_conf','forward_address',
						'label', 'nickname', 'created_at', 'updated_at')
				->get();
		foreach($slots as &$slot){
			$slot->min_conf = intval($slot->min_conf);
			$slot->tokens = json_decode($slot->tokens, true);
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
				->select('public_id','tokens','webhook','min_conf','forward_address',
						 'label', 'nickname', 'created_at', 'updated_at')
				->first();
		if(!$slot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$slot->min_conf = intval($slot->min_conf);
		$slot->tokens = json_decode($slot->tokens, true);
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
		$input = Input::all();
		$slot = Slot::where('userId', '=', $user->id)
					  ->where('public_id', '=', $slotId)
					  ->orWhere('nickname', '=', $slotId)
					  ->first();
		if(!$slot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$payments = Payment::where('slotId', '=', $slot->id);
		
		if(isset($input['incomplete'])){
			if(boolval($input['incomplete'])){
				$andComplete = 0;
			}
			else{
				$andComplete = 1;
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
		$payments = $payments->select('id', 'address', 'asset', 'total', 'received', 'complete', 'init_date', 'complete_date',
							 'reference', 'tx_info', 'cancelled', 'cancel_time')
					->get();
		foreach($payments as &$payment){
			$payment->tx_info = json_decode($payment->tx_info);
			$payment->total = intval($payment->total);
			$payment->received = intval($payment->received);
			$payment->complete = boolval($payment->complete);
			$payment->cancelled = boolval($payment->cancelled);
			$payment->id = intval($payment->id);
			$payment->slot_id = $slot->public_id;
		}
		return Response::json($payments);
	}
	
	/**
	 * creates a new payment slot
	 * @return Response
	 * */	
	public function create()
	{
		$user = User::$api_user;
		$input = Input::all();
		$required = array('tokens');
		if(!isset($input['tokens']) OR (!is_array($input['tokens']) AND trim($input['tokens']) == '') 
			OR (is_array($input['tokens']) AND count($input['tokens']) == 0)){
				$output = array('error' => 'tokens required');
				return Response::json($output, 400);
		}
		if(!is_array($input['tokens'])){
			$decode_tokens = json_decode($input['tokens'], true);
			if(is_array($decode_tokens)){
				$input['tokens'] = $decode_tokens;
			}
			else{
				$input['tokens'] = array(trim($input['tokens']));
			}
		}
		foreach($input['tokens'] as &$token){
			$token = strtoupper($token);
		}

		$webhook = null;
		if(isset($input['webhook']) AND trim($input['webhook']) != ''){
			$webhook = trim($input['webhook']);
			if(!filter_var($webhook, FILTER_VALIDATE_URL)){
				$output = array('error' => 'Invalid webhook, please use a real URL');
				return Response::json($output, 400);
			}
		}
		$address = null;
		$min_conf = 0;
		
		if(isset($input['forward_address'])){
			$address = $input['forward_address'];
			if(trim($address) != ''){
				if(!AddressValidator::isValid($address)){
					$output = array('error' => 'Invalid BTC address');
					return Response::json($output, 400);
				}
			}		
		}
			
		//check if assets are real
		$xchain = xchain(); 
		try{
			foreach($input['tokens'] as $token){
				$checkAsset = $xchain->getAsset($token);
				if(!$checkAsset){
					throw new Exception('Could not get asset');
				}				
			}
		}
		catch(Exception $e){
			$output = array('error' => 'Invalid Asset');
			return Response::json($output, 400);
		}

		if(isset($input['min_conf'])){
			$min_conf = intval($input['min_conf']);
			if($min_conf < 0){
				$output = array('error' => 'Invalid minimum confirmations');
				return Response::json($output, 400);
			}
		}
		
		$slot = new Slot;
		$slot->userId = $user->id;
		$slot->public_id = str_random(20);
		$slot->tokens = json_encode($input['tokens']);
		$slot->webhook = $webhook;
		$slot->min_conf = $min_conf;
		$slot->forward_address = $address;
		if(isset($input['label'])){
			$slot->label = trim($input['label']);
		}
		if(isset($input['nickname'])){
			$slot->nickname = trim($input['nickname']);
		}
		$save = $slot->save();
		$output = array();
		$output['public_id'] = $slot->public_id;
		$output['tokens'] = $input['tokens'];
		$output['webhook'] = $slot->webhook;
		$output['min_conf'] = $slot->min_conf;
		$output['forward_address'] = $slot->forward_address;
		$output['label'] = $slot->label;
		$output['nickname'] = $slot->nickname;
		$output['created_at'] = $slot->created_at;
		$output['updated_at'] = $slot->updated_at;
		return Response::json($output);		
	}
	
	/**
	 * updates DB info for a specific slot
	 * @param string $slotId the public_id of the slot
	 * @return Response
	 * */
	public function update($slotId)
	{
		$user = User::$api_user;
		$input = Input::all();
		$getSlot = Slot::where('userId', '=', $user->id)
						->where('public_id', '=', $slotId)
						->orWhere('nickname', '=', $slotId)->first();
		if(!$getSlot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$updateable = array('tokens', 'webhook', 'min_conf', 'forward_address', 'label', 'nickname');
		$fieldsUpdated = 0;
		foreach($updateable as $field){
			if(isset($input[$field])){
				if($field == 'min_conf'){
					$input[$field] = intval($input[$field]);
				}
				if($field == 'forward_address'){
					if(!AddressValidator::isValid($input[$field])){
						$output = array('error' => 'Invalid BTC address');
						return Response::json($output, 400);
					}					
				}
				if($field == 'webhook' AND trim($input[$field]) != ''){
					if(!filter_var($input[$field], FILTER_VALIDATE_URL)){
						$output = array('error' => 'Invalid webhook, please use a real URL');
						return Response::json($output, 400);
					}
				}
				if($field == 'tokens'){
					//do some extra validation on tokens
					if(!is_array($input[$field])){
						$decode_tokens = json_decode($input[$field], true);
						if(is_array($decode_tokens)){
							$input[$field] = $decode_tokens;
						}
						else{
							$old = trim($input[$field]);
							$input[$field] = array();
							if($old != ''){
								$input[$field][] = $old;
							}
						}
					}
					foreach($input[$field] as $tk => $tv){
						$input[$field][$tk] = strtoupper($tv);
					}
					
					$xchain = xchain(); 
					try{
						foreach($input[$field] as $token){
							$checkAsset = $xchain->getAsset($token);
							if(!$checkAsset){
								throw new Exception('Invalid Asset');
							}
						}
					}
					catch(Exception $e){
						$output = array('error' => 'Invalid Asset');
						return Response::json($output, 400);
					}			
					$getSlot->$field = json_encode($input[$field]);		
					
				}
				else{
					//normal field update
					$getSlot->$field = trim($input[$field]);
				}
				$fieldsUpdated++;
			}
		}
		if($fieldsUpdated == 0){
			$output = array('error' => 'No fields updated');
			return Response::json($output, 400);
		}
		$save = $getSlot->save();
		if(!$save){
			$output = array('error' => 'Error saving slot');
			return Response::json($output, 500);
		}
		$output = array();
		$output['public_id'] = $getSlot->public_id;
		$output['tokens'] = json_decode($getSlot->tokens, true);
		$output['webhook'] = $getSlot->webhook;
		$output['min_conf'] = $getSlot->min_conf;
		$output['forward_address'] = $getSlot->forward_address;
		$output['label'] = $getSlot->label;
		$output['nickname'] = $getSlot->nickname;
		$output['created_at'] = $getSlot->created_at;
		$output['updated_at'] = $getSlot->updated_at;
		return Response::json($output);
	}
	
	/**
	 * removes a slot from client's account.
	 * @param string $slotId the public_id of the slot
	 * @return Response
	 * */	
	public function delete($slotId)
	{
		$user = User::$api_user;
		$getSlot = Slot::where('userId', '=', $user->id)
						->where('public_id', '=', $slotId)
						->orWhere('nickname', '=', $slotId)->first();
		if(!$getSlot){
			$output = array('error' => 'Invalid slot ID');
			return Response::json($output, 400);
		}
		$getSlot->delete();
		return Response::json(array('result' => true));
	}
}
