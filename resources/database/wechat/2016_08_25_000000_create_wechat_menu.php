<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_menu', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('accountId')->comment('账户ID')->nullable();
            $table->text('data')->comment('菜单数据')->nullable();
            
            $table->index(['accountId']);
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "wechat_menu comment '微信菜单'");

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
