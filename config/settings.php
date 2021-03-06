<?php

return array(
	'sweep_tx_fee' => 60000,
	'sweep_tx_dust' => 5430,
	'min_fuel_cost' => 20000,
	'fee_per_byte' => 40,
	'sweep_fuel_source' => env('TOKENSLOT_FUEL_SOURCE'),
	'sweep_fuel_source_uuid' => env('TOKENSLOT_FUEL_UUID'),
	'peggable_tokens' => array('BTC','FLDC','XCP','BITCRYSTALS', 'HLTH'),
	'peg_currencies' => array('USD', 'BTC', 'EUR',),
	'peg_currency_denoms' => array('USD' => 100, 'BTC' => 100000000, 'EUR' => 100,),
	'peggable_token_aliases' => array(),
    'hard_pegs' => array('HLTH' => array('USD' => 20)),
);
