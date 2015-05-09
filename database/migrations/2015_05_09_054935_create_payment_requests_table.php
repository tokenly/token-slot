<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('payment_requests', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('slotId')->unsigned();
			$table->foreign('slotId')->references('id')->on('slots')->onDelete('cascade'); //the slot tells us what asset to check
			$table->string('address')->unique(); //each payment address should be unique
			$table->integer('total')->default(0); //order total in satoshis
			$table->integer('received')->default(0);//total seen so far, in satoshis
			$table->boolean('complete')->default(0);
			$table->dateTime('init_date');
			$table->dateTime('complete_date')->nullable();
			$table->mediumText('tx_info')->nullable(); //json encoded array of transaction IDs received in the payment
			$table->string('IP');
			$table->string('reference')->nullable(); //additional order reference field
			$table->index('reference');
			$table->index('address');
		});
	}
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('payment_requests');
	}
}
