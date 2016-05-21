<?php

function xchain()
{
	return app('Tokenly\XChainClient\Client');
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
	$entropy['fees'] = $tx['bitcoinTx']['fees'];
	$hash = hash('sha256', hash('sha256', serialize($entropy)));
	return $hash;
}
