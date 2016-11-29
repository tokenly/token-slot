<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMinConfToPaymentRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
	public function up()
	{
		Schema::table('payment_requests', function(Blueprint $table)
		{
			$table->integer('min_conf')->nullable();
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
			$table->dropColumn('min_conf');
		});
	}

}
