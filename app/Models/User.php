<?php
class User extends Eloquent
{
	public static function generateKey($email = null)
	{
		$key = hash('sha256', $email.str_random(64).mt_rand(0,10000));
		return $key;
	}
	
}
