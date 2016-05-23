<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\Slot, Payment, User, Config, Exception;

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
        if(count($payments) == 0){
            return false;
        }
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
				if(isset($item['send_info'][0]) AND is_array($item['send_info'][0])){
					foreach($item['send_info'] as $info){
						$this->info('Payment of '.$info['quantity'].' '.$info['asset'].' from '.$getPayment->address.' sent to '.$info['destination'].' - '.$info['txid']);
					}
				}
				else{
					$this->info('Payment of '.$item['send_info']['quantity'].' '.$item['send_info']['asset'].' from '.$getPayment->address.' sent to '.$item['send_info']['destination'].' - '.$item['send_info']['txid']);
				}
			}
		}
	}
	
	protected function sendTokens($payments)
	{
		foreach($payments as $k => $item){
			$token = $item['payment']->token;
			$address = $item['forward_address'];
			$payments[$k]['send_info'] = false;
			$send = false;
			try{
				if(!isset($item['balances'][$token]) OR $item['balances'][$token] <= 0){
					continue;
				}
				if(is_array($address) AND $token != 'BTC'){
					$getAsset = $this->xchain->getAsset($token);
					if(!$getAsset['divisible'] AND isset($address[0])){
						//can only forward non divisibles to a single address
						$set_addr = false;
						foreach($address as $addr => $split){
							$set_addr = $addr;
							break;
						}
						$address = $set_addr;
					}
				}
				if(is_array($address)){
					$send = array();
					foreach($address as $addr => $split){
						$split = $split / 100;
						$amount = round($item['balances'][$token] * $split);
						if($token == 'BTC'){
							$amount -= $this->tx_fee;
						}
						if($amount <= 0){
							continue;
						}
						$send[] = $this->xchain->send($item['payment']->payment_uuid, $addr,
													$amount/self::SATOSHI_MOD, $token, $this->tx_fee/self::SATOSHI_MOD, $this->tx_dust/self::SATOSHI_MOD);
					}
				}
				else{
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
			}
			catch(Exception $e){
				$this->error('Error sending tokens: ['.$item['payment']->address.'] '.$e->getMessage());
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
				$min_cost = ($this->min_fuel_cost * $item['forward_count']);
				if($item['prime_btc'] < $min_cost){
					$item['prime_btc'] = $min_cost;
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
		$perFee = ($this->tx_fee + $this->tx_dust) * 2;
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
				$feeDiff = $thisFee - ($perFee * $item['forward_count']);
				if($feeDiff < 0){
					$item['prime_btc'] = ($perFee * $item['forward_count']) - $thisFee;
					$btc_needed += $item['prime_btc'] + ($this->tx_fee * $item['forward_count']);
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
			$tx_count += $item['forward_count'];
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
			
			if(trim($payment->forward_address) != ''){
				$item['forward_address'] = $payment->forward_address;
			}
			elseif(trim($item['slot']->forward_address != '')){
				$item['forward_address'] = $item['slot']->forward_address;
			}
			else{
				$getUser = User::find($item['slot']->userId);
				$item['forward_address'] = $getUser['forward_address'];
			}
			
			$decode_forward = json_decode($item['forward_address'], true);
			$item['forward_count'] = 1;
			if(is_array($decode_forward)){ //split addresses
				$item['forward_address'] = $decode_forward;
				$item['forward_count'] = count($item['forward_address']);
				foreach($item['forward_address'] as $address => $split){
					if($split <= 0){
						unset($item['forward_address'][$address]);
						continue;
					}
				}
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
