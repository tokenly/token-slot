<?php

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
	protected $table = 'payment_requests';
	public $timestamps = false;
	
	/**
	 * finds a payment request
	 * @param mixed $paymentId the ID of the payment, bitcoin address or unique reference
	 * @param User $user the object for the client account you want to check under. leave false to auto get API user
	 * @return Array payment object
	 * */
	public static function getPayment($paymentId, $user = false)
	{
		if(!$user){
			$user = User::$api_user;
		}
		$slots = Slot::where('userId', '=', $user->id)->get();
		$valid_slots = array();
		foreach($slots as $slot){
			$valid_slots[] = $slot->id;
		}		
		$get = Payment::whereIn('slotId', $valid_slots)
							  ->where(function($query) use($paymentId){
								  return $query->where('id', '=', $paymentId)
												->orWhere('address', '=', $paymentId)
												->orWhere('reference', '=', $paymentId);
								  
							  })
							  ->select('id', 'slotId', 'address', 'total', 'received',
									   'complete', 'init_date', 'complete_date',
									   'tx_info', 'reference', 'cancelled', 'cancel_time')
							  ->first();	
		return $get;
		
	}
	
}
