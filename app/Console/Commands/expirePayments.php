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
        $seconds_old = env('EXPIRE_OLD_ADDRESSES_SECONDS', 172800);
        $count_expired = $this->expirePayments($seconds_old, $this->option('max'));
        $this->comment('['.DateProvider::now().'] Finished expiring payments. Expired '.$count_expired.' payment(s).');
    }

    // ------------------------------------------------------------------------
    
    protected function expirePayments($seconds_old, $limit=null) {
        $payment_repository = app('App\Repositories\PaymentRequestRepository');
        $unarchived_payments = $payment_repository->findUnarchivedOlderThanSeconds($seconds_old, $limit);
        foreach($unarchived_payments as $unarchived_payment) {
            $this->archivePayment($unarchived_payment);
        }

        return $unarchived_payments->count();
    }

    protected function archivePayment(Payment $unarchived_payment) {
        $payment_repository = app('App\Repositories\PaymentRequestRepository');

        // send request to XChain to delete (archive) the payment address
        try{
            xchain()->destroyPaymentAddress($unarchived_payment['payment_uuid']);
        }
        catch(Exception $e){
            $this->error('Error archiving #'.$unarchived_payment['id'].': '.$e->getMessage());
            return false;
        }
        
        // update repository
        $payment_repository->update($unarchived_payment, [
            'archived' => true,
            'archived_date' => DateProvider::now(),
        ]);
        
        $this->info('Payment #'.$unarchived_payment['id'].' archived');
    }
}
