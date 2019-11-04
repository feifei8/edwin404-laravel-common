<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_token', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('token', 64)->nullable()->comment('Token');
            $table->string('data', 500)->nullable()->comment('Data');
            $table->timestamp('expireTime')->nullable()->comment('过期时间');

            $table->unique(['token']);
            $table->index(['expireTime']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::drop('client_token');
    }
}
