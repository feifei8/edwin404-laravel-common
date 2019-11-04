<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatQrcode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_qrcode', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('accountId')->nullable()->comment('微信账号ID,wechat_account表');
            /**
             * @see \Edwin404\Wechat\Types\WechatQrcodeStatus
             */
            $table->tinyInteger('status')->nullable()->comment('二维码状态 1有效 2无效');

            /**
             * @see \Edwin404\Wechat\Types\WechatQrcodeType
             */
            $table->tinyInteger('type')->nullable()->comment('类型 1临时二维码 2永久二维码');
            $table->timestamp('expire')->nullable()->comment('过期时间 对于永久我二维码该字段无效');
            $table->string('ticket', 200)->nullable()->comment('Ticket');
            $table->string('url', 200)->nullable()->comment('二维码的微信Url');
            $table->integer('scene')->nullable()->comment('参数值,通常为数字的自增,和业务无关');
            $table->string('usage', 50)->nullable()->comment('二维码用途,参数使用冒号分割,和业务相关');

            $table->integer('sceneTemp')->nullable()->comment('临时二维码的参数值');

            $table->unique(['accountId','usage','type']);

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
