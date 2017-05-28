<?php

return array(
	'sweep_tx_fee' => 60000,
	'sweep_tx_dust' => 5430,
	'min_fuel_cost' => 60000,
	'fee_per_byte' => 275,
	'sweep_fuel_source' => env('TOKENSLOT_FUEL_SOURCE'),
	'sweep_fuel_source_uuid' => env('TOKENSLOT_FUEL_UUID'),
	'peggable_tokens' => array('BTC','LTBC','FLDC','GEMZ','SWARM','SJCX','XCP','BITCRYSTALS'),
	'peg_currencies' => array('USD', 'BTC', 'EUR',),
	'peg_currency_denoms' => array('USD' => 100, 'BTC' => 100000000, 'EUR' => 100,),
	'peggable_token_aliases' => array('LTBCOIN' => 'LTBC'),
);
