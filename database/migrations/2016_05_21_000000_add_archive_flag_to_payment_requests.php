<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;



class AddArchiveFlagToPaymentRequests extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_requests', function(Blueprint $table)
		{
			$table->boolean('archived')->default(false);
			$table->dateTime('archived_date')->nullable();
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
			$table->dropColumn('archived_date');
			$table->dropColumn('archived');
		});
	}

}
