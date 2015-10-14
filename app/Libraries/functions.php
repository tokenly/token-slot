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

//generates a unique TX ID based on inputs/outputs of a bitcoin transaction from xchain (instead of relying on normal txid)
function generateInternalTXID($tx)
{
	$entropy = array();
	$entropy['address'] = $tx['notifiedAddress'];
	$entropy['inputs'] = array();
	foreach($tx['bitcoinTx']['vin'] as $vin){
		$input = array();
		$input['txid'] = $vin['txid'];
		$input['vout'] = $vin['vout'];
		$input['n'] = $vin['n'];
		$input['addr'] = $vin['addr'];
		$input['value'] = $vin['value'];
		$entropy['inputs'][] = $input;
	}
	$entropy['outputs'] = $tx['bitcoinTx']['vout'];
	$entropy['fees'] = $tx['fees'];
	$hash = hash('sha256', hash('sha256', serialize($entropy)));
	return $hash;
}
