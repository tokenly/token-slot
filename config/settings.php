<?php

return array(
	'xchain_url' => env('XCHAIN_CONNECTION_URL'),
	'xchain_user' => env('XCHAIN_API_TOKEN'),
	'xchain_secret' =>env('XCHAIN_API_KEY'),
	'sweep_tx_fee' => 5000,
	'sweep_tx_dust' => 5430,
	'min_fuel_cost' => 5000,
	'sweep_fuel_source' => '',
	'sweep_fuel_source_uuid' => '',
);
