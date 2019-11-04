<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('categoryId')->comment('分类ID')->nullable();

            $table->string('title', 200)->nullable()->comment('标题');
            $table->string('summary', 400)->nullable()->comment('摘要');
            $table->text('content')->nullable()->comment('内容');

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
