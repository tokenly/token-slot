<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

	public static $api_user = false;

    protected static $unguarded = true;


    protected $casts = [
        'activated' => 'boolean',
    ];

	public static function generateKey($email = null)
	{
		$key = substr(hash('sha256', $email.str_random(64).mt_rand(0,10000)), 0, 32);
		return $key;
	}
	
}
