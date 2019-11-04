<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_category', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('pid')->comment('父ID')->nullable();
            $table->integer('sort')->comment('排序，越小越靠前')->nullable();

            $table->string('name', 20)->nullable()->comment('名称');

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
