<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlotsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('slots', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('userId')->unsigned();
			$table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
			$table->string('public_id')->unique();
			$table->text('tokens');
			$table->string('webhook')->nullable();
			$table->integer('min_conf')->default(0);
			$table->text('forward_address')->nullable();
			$table->string('label')->nullable();
			$table->timestamps();
		});
	}
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('slots');
	}

}
