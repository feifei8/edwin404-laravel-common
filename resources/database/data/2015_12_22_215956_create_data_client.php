<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataClient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_client', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();
            $table->string('key', 32)->nullable()->comment('Key');
            $table->string('name', 20)->nullable()->comment('应用名称');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
