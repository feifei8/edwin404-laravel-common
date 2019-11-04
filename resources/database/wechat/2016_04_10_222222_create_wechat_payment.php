<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_payment', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('accountId')->nullable()->comment('微信AccountID');

            $table->string('merchantId', 64)->nullable()->comment('MerchantId');
            $table->string('key', 64)->nullable()->comment('Key');

            /**
             * ALTER TABLE `wechat_payment` ADD `dataCert` TEXT NULL DEFAULT NULL COMMENT  'cert.pem文件内容';
             * ALTER TABLE `wechat_payment` ADD `dataKey` TEXT NULL DEFAULT NULL COMMENT  'key.pem文件内容';
             */
            $table->text('dataCert')->nullable()->comment('cert.pem文件内容');
            $table->text('dataKey')->nullable()->comment('key.pem文件内容');

            $table->index(['accountId']);
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
