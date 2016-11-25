<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\Base\APIController;
use Exception;
use Illuminate\Http\JsonResponse;
use LinusU\Bitcoin\AddressValidator;
use User, App\Models\Slot, Input, Response, Payment;
use Log;

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
			$decode_forward = json_decode($slot->forward_address, true);
			if(is_array($decode_forward)){
				$slot->forward_address = $decode_forward;
			}
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
		$decode_forward = json_decode($slot->forward_address, true);
		if(is_array($decode_forward)){
			$slot->forward_address = $decode_forward;
		}		
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
			$decode_forward = json_decode($payment->forward_address, true);
			if(is_array($decode_forward)){
				$payment->forward_address = $decode_forward;
			}			
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
        $token_list = array();
        if(isset($input['tokens'])){
            if(!is_array($input['tokens']) AND trim($input['tokens']) != ''){
                $decode_tokens = json_decode($input['tokens'], true);
                if(is_array($decode_tokens)){
                    $input['tokens'] = $decode_tokens;
                }
                else{
                    $input['tokens'] = array(trim($input['tokens']));
                }
                $token_list = $input['tokens'];
            }
            elseif(is_array($input['tokens'])){
                $token_list = $input['tokens'];
            }
            
        }

		foreach($token_list as $k => $token){
			$token_list[$k] = strtoupper($token);
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
			if(!is_array($address)){
				$attempt_decode = json_decode($address, true);
				if(is_array($attempt_decode)){
					$address = $attempt_decode;
				}
			}			
			if(is_array($address)){
				$forward_list = array();
				$used_split = 100;
				foreach($address as $k => $r){
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
				$address = $forward_list;
			}
			else{
				if(trim($address) != ''){
					if(!AddressValidator::isValid($address)){
						$output = array('error' => 'Invalid BTC address '.$address);
						return Response::json($output, 400);
					}
				}	
			}	
		}
			
		//check if assets are real
		$xchain = xchain(); 
		try{
            if(count($token_list) > 0){
                foreach($token_list as $token){
                    $checkAsset = $xchain->getAsset($token);
                    if(!$checkAsset){
                        throw new Exception('Could not get asset');
                    }				
                }
            }
		}
		catch(Exception $e){
			Log::error($e->getMessage().' ('.__FUNCTION__.')');
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
		
		if(is_array($address)){
			$address = json_encode($address);
		}
		
		$slot = new Slot;
		$slot->userId = $user->id;
		$slot->public_id = str_random(20);
		$slot->tokens = json_encode($token_list);
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
		$output['tokens'] = $token_list;
		$output['webhook'] = $slot->webhook;
		$output['min_conf'] = $slot->min_conf;
		$output['forward_address'] = $slot->forward_address;
		$output['label'] = $slot->label;
		$output['nickname'] = $slot->nickname;
		$output['created_at'] = $slot->created_at;
		$output['updated_at'] = $slot->updated_at;
		
		$decode_forward = json_decode($output['forward_address'], true);
		if(is_array($decode_forward)){
			$output['forward_address'] = $decode_forward;
		}		
		
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
					$address = $input[$field];
					if(!is_array($address)){
						$attempt_decode = json_decode($address, true);
						if(is_array($attempt_decode)){
							$address = $attempt_decode;
						}
					}								
					if(is_array($address)){
						$forward_list = array();
						$used_split = 100;
						foreach($address as $k => $r){
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
						$input[$field] = json_encode($forward_list);
					}
					else{						
						if(!AddressValidator::isValid($address)){
							$output = array('error' => 'Invalid BTC address '.$address);
							return Response::json($output, 400);
						}	
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
						Log::error($e->getMessage().' ('.__FUNCTION__.')');
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
		
		$decode_forward = json_decode($output['forward_address'], true);
		if(is_array($decode_forward)){
			$output['forward_address'] = $decode_forward;
		}
		
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
