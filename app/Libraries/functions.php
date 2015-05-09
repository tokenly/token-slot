<?php

function xchain()
{
	$client = new \Tokenly\XChainClient\Client(
				\Config::get('settings.xchain_url'),
				\Config::get('settings.xchain_user'),
				\Config::get('settings.xchain_secret'));
	return $client;
}


function timestamp()
{
	return date('Y-m-d H:i:s');
}
