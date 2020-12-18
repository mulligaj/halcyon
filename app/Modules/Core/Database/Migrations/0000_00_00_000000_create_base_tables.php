<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration script for installing base tables
 **/
class CreateBaseTables extends Migration
{
	/**
	 * Up
	 **/
	public function up()
	{
		if (!Schema::hasTable('extensions'))
		{
			Schema::create('extensions', function (Blueprint $table)
			{
				$table->increments('id');
				$table->string('name', 100);
				$table->string('type', 20);
				$table->string('element', 100);
				$table->string('folder', 100)->nullable();
				$table->tinyInteger('client_id')->unsigned()->default(0);
				$table->tinyInteger('enabled')->unsigned()->default(0);
				$table->integer('access')->unsigned()->default(0);
				$table->tinyInteger('protected')->unsigned()->default(0);
				$table->text('params')->nullable();
				$table->integer('checked_out')->unsigned()->default(0);
				$table->timestamp('checked_out_time')->nullable();
				$table->integer('ordering')->unsigned()->default(0);
				$table->integer('state')->unsigned()->default(0);
				$table->timestamp('updated_at')->nullable();
				$table->integer('updated_by')->unsigned()->default(0);
				$table->index(['element', 'client_id']);
				$table->index(['element', 'folder', 'client_id']);
				$table->index(['type', 'element', 'folder', 'client_id']);
			});
			//$this->command->info('Created `extensions` table.');
		}

		if (!Schema::hasTable('timeperiods'))
		{
			Schema::create('timeperiods', function (Blueprint $table)
			{
				$table->increments('id');
				$table->string('name', 32);
				$table->string('singular', 32);
				$table->string('plural', 32);
				$table->integer('unixtime')->unsigned()->default(0);
				$table->tinyInteger('months')->unsigned()->default(0);
				$table->tinyInteger('warningtimeperiodid')->unsigned()->default(0);
			});
			//$this->command->info('Created `timeperiods` table.');
		}
	}

	/**
	 * Down
	 **/
	public function down()
	{
		$tables = array(
			'extensions',
			'timeperiods',
		);

		foreach ($tables as $table)
		{
			Schema::dropIfExists($table);
			//$this->command->info('Dropped `' . $table . '` table.');
		}
	}
}
