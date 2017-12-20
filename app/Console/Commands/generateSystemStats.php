<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use User, Transaction, App\Models\Slot, Payment;
use Exception;

class generateSystemStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokenslot:generateSystemStats {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates overall and user specific statistics for system usage';

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
        //find each user in the system
        // - # of users
        // - list off stats for each user
        //      - # of txs
        //      - amounts received in each payment type
        
        //show combined totals
        //show unswept balances
        
        $since = $this->option('since');
        if($since){
            $this->info('Generating stats from '.date('Y-m-d H:i:s', strtotime($since)).' onwards');
        }
        
        $users = User::all();
        $num_users = count($users);
        
        $overall_totals = array();
        $overall_totals['slots'] = 0;
        $overall_totals['payments'] = array('complete' => 0, 'receiving' => 0, 'expired' => 0, 'pending' => 0);
        $overall_totals['tokens_collected'] = array();
        $user_totals = array();
        
        foreach($users as $user){
            
            $user_total = array();
            
            $slots = Slot::where('userId', $user->id)->get();
            if(!$slots){
                continue;
            }
            $count_slots = count($slots);
            $user_total['user'] = $user->email;
            $user_total['user_id'] = $user->id;
            $user_total['slots'] = $count_slots;
            $user_total['payments'] = array('complete' => 0, 'receiving' => 0, 'expired' => 0, 'pending' => 0);
            $user_total['tokens_collected'] = array();
            
            foreach($slots as $slot){
                if($since){
                    $payments = Payment::where('slotId', $slot->id)->where('created_at', '>=', date('Y-m-d H:i:s', strtotime($since)))->get();
                }
                else{
                    $payments = Payment::where('slotId', $slot->id)->get();
                }
                if(!$payments){
                    continue;
                }
                foreach($payments as $payment){
                    if($payment->complete == 1){
                        $user_total['payments']['complete']++;
                        if(!isset($user_total['tokens_collected'][$payment->token])){
                            $user_total['tokens_collected'][$payment->token] = 0;
                        }
                        $user_total['tokens_collected'][$payment->token] += $payment->received;
                    }
                    elseif($payment->received > 0){
                        $user_total['payments']['receiving']++;
                    }
                    elseif($payment->expired == 1){
                        $user_total['payments']['expired']++;
                    }
                    else{
                        $user_total['payments']['pending']++;
                    }
                }
                
            }
            
            
            $user_totals[$user->id] = $user_total;
        }
        
        foreach($user_totals as $user_total){
            $overall_totals['slots'] += $user_total['slots'];
            foreach($user_total['payments'] as $k => $v){
                $overall_totals['payments'][$k] += $v;
            }
            foreach($user_total['tokens_collected'] as $token => $received){
                if(!isset($overall_totals['tokens_collected'][$token])){
                    $overall_totals['tokens_collected'][$token] = 0;
                }
                $overall_totals['tokens_collected'][$token] += $received;
            }
        }
       
       $this->info('Overall stats..');
       print_r($overall_totals);
       $this->info('User specific stats..');
       print_r($user_totals);
        
    }
}
