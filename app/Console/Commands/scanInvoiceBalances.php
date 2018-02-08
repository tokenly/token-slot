<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Payment;

class scanInvoiceBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokenslot:scanInvoiceBalances {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all generated invoices including expired ones and checks their balances';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $xchain = xchain();
        $since = $this->option('since');
        
        $invoices = Payment::select('*');
        if($since){
            $since = date('Y-m-d H:i:s', strtotime($since));
            $invoices = $invoices->where('init_date', '>=', $since);
        }
        $invoices->orderBy('id', 'asc');
        $invoices = $invoices->get();
        
        if(!$invoices OR count($invoices) == 0){
            $this->info('No invoices found');
            return false;
        }
        
        foreach($invoices as $k => $invoice){
            try{
                $balances = $xchain->getBalances($invoice->address);
            }
            catch(Exception $e){
                $this->error('Error retrieving balances for address '.$invoice->address.' ['.$invoice->id.']');
                continue;
            }
            if(is_array($balances)){
                $all_empty = true;
                foreach($balances as $asset => $balance){
                    if($balance > 0){
                        $all_empty = false;
                    }
                }
                if($all_empty){
                    $balances = false;
                }
            }
            if(!$balances OR count($balances) == 0){
                //$this->info('No balances found for address '.$invoice->address.' ['.$invoice->id.']');
                continue;
            }
            
            $forward_address = $invoice->forward_address;
            if(trim($forward_address) == ''){
                $slot = $invoice->slot();
                if($slot){
                    $forward_address = $slot->forward_address;
                    if(trim($forward_address) == ''){
                        $user = $slot->user();
                        if($user){
                            $forward_address = $user->forward_address;
                        }
                    }
                }
            }
            
            $this->info('--------------');
            $this->info('invoice:'.$invoice->address.' ['.$invoice->id.'] total:'.$invoice->total.' '.$invoice->asset.' complete:'.$invoice->complete.' swept:'.$invoice->swept.' expired:'.$invoice->expired);
            $this->info('forward:'.$forward_address.' init_date:'.$invoice->init_date);
            $this->info('Balances:');
            print_r($balances);
            
            
            
            
            
        }
        
    }
}
