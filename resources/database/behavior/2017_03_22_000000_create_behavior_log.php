<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBehaviorLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('behavior_log', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('time')->nullable()->comment('时间');
            $table->string('action', 100)->nullable()->comment('用户行为');

            $table->index(['time', 'action']);

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
