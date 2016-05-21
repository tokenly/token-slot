<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;



class AddInitDateIndexToPaymentRequests extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_requests', function(Blueprint $table)
		{
			$table->index('init_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_requests', function(Blueprint $table)
		{
			$table->dropIndex('payment_requests_init_date_index');
		});
	}

}
