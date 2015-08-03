<?php

return array(
	'xchain_url' => env('XCHAIN_CONNECTION_URL'),
	'xchain_user' => env('XCHAIN_API_TOKEN'),
	'xchain_secret' =>env('XCHAIN_API_KEY'),
	'sweep_tx_fee' => 5000,
	'sweep_tx_dust' => 5430,
	'min_fuel_cost' => 5000,
	'sweep_fuel_source' => env('TOKENSLOT_FUEL_SOURCE'),
	'sweep_fuel_source_uuid' => env('TOKENSLOT_FUEL_UUID'),
	'peggable_tokens' => array('BTC','LTBC','FLDC','GEMZ','SWARM','SJCX','XCP'),
	'peg_currencies' => array('USD', 'BTC'),
	'peg_currency_denoms' => array('USD' => 100, 'BTC' => 100000000),
	'peggable_token_aliases' => array('LTBCOIN' => 'LTBC'),
);
