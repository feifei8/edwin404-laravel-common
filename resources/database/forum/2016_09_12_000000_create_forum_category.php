<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_category', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('pid')->nullable()->comment('上级分类');
            $table->integer('sort')->nullable()->comment('排序');
            $table->string('title', 50)->nullable()->comment('名称');

            $table->string('cover', 100)->nullable()->comment('图标');
            $table->string('desc', 200)->nullable()->comment('描述');

            $table->integer('threadCount')->nullable()->comment('主题数量');
            $table->integer('postCount')->nullable()->comment('帖子数量');

            $table->index(['pid']);

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
