<?php

namespace App\Console\Commands;

use App\Providers\Date\Facade\DateProvider;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Payment;
use Exception;

class expirePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'expirePayments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expires old, unused payment addresses';


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
            ['max', 'm', InputOption::VALUE_OPTIONAL, 'Maximum addresses to expire on this run', 1000],
            ['all', 'a', InputOption::VALUE_OPTIONAL, 'No limit, expire everything', false],
            ['archive-on-error', 'aoe', InputOption::VALUE_OPTIONAL, 'Archive the payment anyway if there is an error from xchain', false],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('['.DateProvider::now().'] begin expiring payments');
        $max = $this->option('max');
        if($this->option('all')){
            $max = null;
        }
        $count_expired = $this->expirePayments($max);
        $this->comment('['.DateProvider::now().'] Finished expiring payments. Expired '.$count_expired.' payment(s).');
    }

    // ------------------------------------------------------------------------
    
    protected function expirePayments($limit=null) {
        $payment_repository = app('App\Repositories\PaymentRequestRepository');
        $unarchived_payments = $payment_repository->findUnarchived($limit);
        $this->info('Checking '.count($unarchived_payments).' for expiration');
        foreach($unarchived_payments as $unarchived_payment) {
            $this->archivePayment($unarchived_payment);
        }

        return count($unarchived_payments);
    }

    protected function archivePayment(Payment $unarchived_payment) {
        $payment_repository = app('App\Repositories\PaymentRequestRepository');

        // send request to XChain to delete (archive) the payment address
        try{
            xchain()->destroyPaymentAddress($unarchived_payment['payment_uuid']);
        }
        catch(Exception $e){
            $this->error('Error destroying payment address for  #'.$unarchived_payment['id'].': '.$e->getMessage());
            $this->info('Payment address uuid: '.$unarchived_payment['payment_uuid']);
            if(!$this->option('archive-on-error')){
                return false;
            }
        }
        
        // update repository
        $payment_repository->update($unarchived_payment, [
            'archived' => true,
            'archived_date' => DateProvider::now(),
        ]);
        
        $this->info('Payment #'.$unarchived_payment['id'].' archived');
    }
}
