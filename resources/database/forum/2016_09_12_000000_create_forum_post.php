<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumPost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_post', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('categoryId')->nullable()->comment('分类ID');
            $table->integer('threadId')->nullable()->comment('帖子ID');

            $table->integer('memberUserId')->nullable()->comment('用户ID');
            $table->integer('replyPostId')->nullable()->comment('回复PostID');

            /** @see \Edwin404\Html\HtmlType */
            $table->tinyInteger('contentType')->nullable()->comment('正文类型');
            $table->text('content')->nullable()->comment('正文');
            $table->text('contentHtml')->nullable()->comment('正文');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::drop('Data');
    }
}
