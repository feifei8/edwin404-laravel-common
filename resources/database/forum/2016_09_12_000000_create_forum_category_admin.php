<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumCategoryAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_category_admin', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('memberUserId')->nullable()->comment('用户ID');
            $table->integer('categoryId')->nullable()->comment('分类ID');

            $table->index(['memberUserId', 'categoryId']);

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
