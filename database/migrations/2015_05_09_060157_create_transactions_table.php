<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('tx_id');
			$table->string('source');
			$table->string('destination');
			$table->string('type'); //e.g send, receive
			$table->string('asset'); //e.g BTC, LTBCOIN
			$table->string('protocol')->default('btc');
			$table->integer('quantity')->default(0); //in satoshis
			$table->integer('block_index');
			$table->dateTime('tx_time');
			$table->index('tx_id');
			$table->index('source');
			$table->index('destination');
		});
	}
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('transactions');
	}

}
