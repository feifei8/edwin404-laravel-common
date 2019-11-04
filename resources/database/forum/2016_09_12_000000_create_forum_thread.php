<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumThread extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_thread', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('memberUserId')->nullable()->comment('memberUserId');
            $table->integer('categoryId')->nullable()->comment('分类ID');

            $table->integer('postCount')->comment('查看数量')->nullable();
            $table->integer('clickCount')->comment('点击数量')->nullable();
            $table->integer('upCount')->comment('赞次数')->nullable();
            $table->integer('downCount')->comment('踩次数')->nullable();

            $table->tinyInteger('isDigest')->comment('精华')->nullable();
            $table->tinyInteger('isTop')->comment('置顶')->nullable();

            $table->timestamp('lastReplyTime')->comment('上次评论时间')->nullable();
            $table->integer('lastReplyMemberUserId')->comment('上次评论用户')->nullable();

            $table->string('title', 200)->nullable()->comment('主题');
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

    }
}
