<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNicknameToSlots extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('slots', function(Blueprint $table)
		{
			$table->string('nickname')->nullable();
			$table->index('nickname');
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
			$table->dropColumn('nickname');
		});
	}
}
