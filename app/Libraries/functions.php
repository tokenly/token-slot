<?php

function xchain()
{
	$client = new \Tokenly\XChainClient\Client(
				\Config::get('settings.xchain_url'),
				\Config::get('settings.xchain_user'),
				\Config::get('settings.xchain_secret'));
	return $client;
}
