<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_account', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('name', 20)->nullable()->comment('名称');

            $table->boolean('enable')->nullable()->comment('开启');
            $table->string('appId', 50)->nullable()->comment('APP_ID');
            $table->string('appSecret', 50)->nullable()->comment('APP_SECRET');
            $table->string('appToken', 50)->nullable()->comment('APP_TOKEN');

            $table->string('alias', 32)->nullable()->comment('账号加密别名');

            $table->tinyInteger('authStatus')->nullable()->default(1)->comment('第三方授权状态: 1正常 2已取消');

            $table->tinyInteger('authType')->nullable()->comment('授权类型 1服务器配置公众号 2第三方授权公众号');

            $table->string('appEncodingKey', 50)->nullable()->comment('APP_EncodingAESKey');

            $table->string('authorizerRefreshToken')->nullable()->default(1)->comment('授权类型=2:获取（刷新）授权公众号的接口调用凭据（令牌）');

            $table->string('avatar', 200)->nullable()->comment('头像');
            /**
             * @see \Edwin404\Wechat\Types\WechatServiceInfo
             */
            $table->tinyInteger('serviceInfo')->nullable()->comment('公众号类型');
            /**
             * @see \Edwin404\Wechat\Types\WechatVerifyInfo
             */
            $table->tinyInteger('verifyInfo')->nullable()->comment('认证类型');

            $table->string('username', 20)->nullable()->comment('原始ID');
            $table->string('wechat', 50)->nullable()->comment('微信号');

            $table->string('func', 500)->nullable()->comment('权限集列表(JSON)');

            $table->unique(['alias']);
            $table->unique(['appId', 'authType']);

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
