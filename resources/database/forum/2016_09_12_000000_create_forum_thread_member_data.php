<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumThreadMemberData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_thread_member_data', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('threadId')->nullable()->comment('帖子ID');
            $table->integer('memberUserId')->comment('用户ID')->nullable();

            $table->tinyInteger('up')->comment('赞')->nullable();
            $table->tinyInteger('down')->comment('踩')->nullable();
            $table->tinyInteger('fav')->comment('收藏')->nullable();

            $table->index(['threadId', 'memberUserId']);
            $table->index(['memberUserId']);

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
