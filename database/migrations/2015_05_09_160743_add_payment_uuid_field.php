<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentUuidField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_requests', function(Blueprint $table)
		{
			$table->string('payment_uuid');
			$table->index('payment_uuid');
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
			$table->dropColumn('payment_uuid');
		});
	}

}
