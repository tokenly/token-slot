<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Slot, Payment, User, Config, Exception;

class sweepTokens extends Command {
	
	const SATOSHI_MOD = 100000000;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'sweepTokens';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gathers all tokens sitting in payment addresses and sweeps them away to a forwading address';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->xchain = xchain();
		$this->tx_fee = Config::get('settings.sweep_tx_fee');
		$this->tx_dust = Config::get('settings.sweep_tx_dust');
		$this->fuel_source = Config::get('settings.sweep_fuel_source');
		$this->fuel_source_id = Config::get('settings.sweep_fuel_source_uuid');
		$this->min_fuel_cost = Config::get('settings.min_fuel_cost');
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$payments = $this->getUnsweptPayments();
		$list = $this->getBasePaymentData($payments);
		$prep = $this->prepSendAmounts($list);
		$prime = $this->primeAddressInputs($prep);

		$send = $this->sendTokens($prep);
		$save = $this->saveSweepData($send);
	}
	
	protected function saveSweepData($payments)
	{
		foreach($payments as $item){
			if($item['send_info']){
				$getPayment = Payment::find($item['payment']->id);
				$getPayment->swept = 1;
				$getPayment->sweep_info = json_encode($item['send_info']);
				$getPayment->save();
				$this->info('Payment of '.$item['send_info']['quantity'].' '.$item['send_info']['asset'].' from '.$getPayment->address.' sent to '.$item['send_info']['destination'].' - '.$item['send_info']['txid']);
			}
		}
	}
	
	protected function sendTokens($payments)
	{
		foreach($payments as $k => $item){
			$token = $item['payment']->token;
			$address = $item['forward_address'];
			$send = false;
			try{
				if($token == 'BTC'){
					if($item['sweep_outputs']){
						//BTC payment.. sweep it all to their address
						$send = $this->xchain->sweepAllAssets($item['payment']->payment_uuid, $address);
					}
				}
				else{
					$balance = $item['balances'][$token];
					$send = $this->xchain->send($item['payment']->payment_uuid, $address,
												$balance/self::SATOSHI_MOD, $token, $this->tx_fee/self::SATOSHI_MOD, $this->tx_dust/self::SATOSHI_MOD);
												
				}
			}
			catch(Exception $e){
				$this->error('Error sending tokens: '.$e->getMessage());
				$send = false;
			}
			
			if($send AND is_array($send)){
				$item['send_info'] = $send;
			}
			else{
				$this->error('Unkown error sending tokens');
				$item['send_info'] = false;
			}
			$payments[$k] = $item;
		}
		return $payments;
	}
	
	protected function primeAddressInputs($payments)
	{
		foreach($payments as $item){
			if($item['prime_btc'] > 0){
				if($item['prime_btc'] < $this->min_fuel_cost){
					$item['prime_btc'] = $this->min_fuel_cost;
				}
				try{
					$prime_input = $this->xchain->send($this->fuel_source_id, $item['payment']['address'], $item['prime_btc']/self::SATOSHI_MOD,
													'BTC', $this->tx_fee/self::SATOSHI_MOD);
				}
				catch(Exception $e){
					$this->error('Error priming: '.$e->getMessage());
					sleep(2);
					try{
						$prime_input = $this->xchain->send($this->fuel_source_id, $item['payment']['address'], $item['prime_btc']/self::SATOSHI_MOD,
														'BTC', $this->tx_fee/self::SATOSHI_MOD);
					}
					catch(Exception $e){
						$this->error('Error priming (attempt 2): '.$e->getMessage());
					}
				}
			}
			sleep(2);
		}
		sleep(5);
	}
	
	protected function prepSendAmounts($payments)
	{
		$list = $payments;
		$tx_count = 0;
		$btc_needed = 0;
		$perFee = $this->tx_fee + $this->tx_dust;
		foreach($list as $k => &$item){
			$asset = $item['payment']->token;
			$item['prime_btc'] = 0;
			$item['sweep_outputs'] = false;
			
			$found = false;
			if(isset($item['balances'][$asset]) AND $asset != 'BTC'){
				$found = true;
				$thisFee = 0;
				if(isset($item['balances']['BTC'])){
					$thisFee += $item['balances']['BTC'];
				}
				$feeDiff = $thisFee - $perFee;
				if($feeDiff < 0){
					$item['prime_btc'] = $perFee - $thisFee;
					$btc_needed += $item['prime_btc'] + $this->tx_fee;
					$tx_count++;
				}
			}
			elseif(isset($item['balances']['BTC']) AND $asset == 'BTC'){
				$found = true;
				$item['sweep_outputs'] = true;
			}
			if(!$found){
				unset($list[$k]);
				$this->error('Payment balance not found for '.$item['payment']['address']);
				continue;
			}
			$tx_count++;
		}
		$total_fuel = $this->getFuelBalance();
		if($btc_needed > $total_fuel){
			throw new Exception('Not enough fuel in '.$this->fuel_source.' - needs '.(($btc_needed - $total_fuel)/self::SATOSHI_MOD));
		}
		return $list;
	}
	
	protected function getBasePaymentData($payments)
	{
		$list = array();
		foreach($payments as $k => $payment){
			$item = array();
			$item['payment'] = $payment;
			$item['slot'] = Slot::find($payment->slotId);
			try{
				$item['balances'] = $balances = $this->xchain->getBalances($payment->address, true);
			}
			catch(Exception $e){
				$this->error($e->getMessage());
				unset($payments[$k]);
				continue;
			}
			$item['forward_address'] = $item['slot']->forward_address;
			if(trim($item['forward_address']) == ''){
				$getUser = User::find($item['slot']->userId);
				$item['forward_address'] = $getUser['forward_address'];
			}
			$list[] = $item;
		}
		return $list;
	}
	
	protected function getUnsweptPayments()
	{
		$get = Payment::where('swept', '=', 0)->where('complete', '=', 1)->get();
		return $get;
	}
	
	protected function getFuelBalance()
	{
		try{
			$balance = $this->xchain->getBalances($this->fuel_source, true);
		}
		catch(Exception $e){
			$this->error('Error getting fuel balance: '.$e->getMessage());
			return 0;
		}
		if(isset($balance['BTC'])){
			return $balance['BTC'];
		}
		return 0;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [

		];
	}

}
