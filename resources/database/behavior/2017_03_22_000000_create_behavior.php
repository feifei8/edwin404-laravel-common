<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBehavior extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('behavior', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('action', 100)->nullable()->comment('用户行为');
            /** @see \Edwin404\Behavior\Types\BehaviorPeriod */
            $table->tinyInteger('period')->nullable()->comment('时间');
            $table->integer('hits')->nullable()->comment('用户行为');

            $table->index(['action', 'period']);

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
