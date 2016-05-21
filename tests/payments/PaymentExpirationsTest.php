<?php

use Carbon\Carbon;
use \PHPUnit_Framework_Assert as PHPUnit;

class PaymentExpirationsTest extends TestCase {

    // yes, this test requires a database to be set up
    protected $use_database = true;

    /**
     * Generates a client using the console command and validates the attributes
     */
    public function testPaymentsExpire() {
        $payment_repository = app('App\Repositories\PaymentRequestRepository');
        $xchain_mock_recorder = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();

        // setup
        $old_slot = app('SlotHelper')->newSlot();
        $old_payment = app('PaymentHelper')->newPayment($old_slot, ['init_date' => Carbon::now()->subDays(3)]);

        $new_slot = app('SlotHelper')->newSlot();
        $new_payment = app('PaymentHelper')->newPayment($new_slot);

        // run the expire command
        app('Illuminate\Contracts\Console\Kernel')->call('expirePayments', ['--max' => 5]);

        // make sure old payments are archived
        $reloaded_old_payment = $payment_repository->findbyId($old_payment['id']);
        PHPUnit::assertTrue($reloaded_old_payment['archived']);

        // make sure new payments are not expired
        $reloaded_new_payment = $payment_repository->findbyId($new_payment['id']);
        PHPUnit::assertFalse($reloaded_new_payment['archived']);

        // make sure xchain methods were called
        PHPUnit::assertCount(1, $xchain_mock_recorder->calls);
        PHPUnit::assertEquals('/addresses/'.$old_payment['payment_uuid'], $xchain_mock_recorder->calls[0]['path']);
    }




}
