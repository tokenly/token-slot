<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpireTimeoutToSlots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
	public function up()
	{
		Schema::table('slots', function(Blueprint $table)
		{
			$table->integer('expire_timeout')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('slots', function(Blueprint $table)
		{
			$table->dropColumn('expire_timeout');
		});
	}
}
