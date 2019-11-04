<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatSubscribe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_subscribe', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('accountId')->comment('账户ID')->nullable();
            $table->text('reply')->comment('菜单数据')->nullable();

            $table->index(['accountId']);
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "wechat_subscribe comment '微信菜单'");

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
