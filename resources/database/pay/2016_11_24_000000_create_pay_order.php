<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_order', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            /** @see \Edwin404\Pay\Types\PayOrderStatus */
            $table->tinyInteger('status')->nullable()->comment('订单状态');

            $table->string('biz', 20)->nullable()->comment('业务:通常表示在当前系统的支付类型,如 购买订单,用户充值');
            $table->integer('bizId')->nullable()->comment('业务ID:在当前业务下的ID标识');

            /** @see \Edwin404\Pay\Types\PayType */
            $table->string('payType', 20)->nullable()->comment('支付方式');
            $table->string('payOrderId', 64)->nullable()->comment('支付订单ID,通常由支付系统返回');

            $table->decimal('feeTotal', 20, 2)->nullable()->comment('支付金额');
            $table->timestamp('timePayCreated')->nullable()->comment('时间:支付订单创建');
            $table->timestamp('timePay')->nullable()->comment('时间:支付');

            $table->decimal('feeRefund', 20, 2)->nullable()->comment('退款金额');
            $table->timestamp('timeRefundCreated')->nullable()->comment('时间:退款申请');
            $table->timestamp('timeRefundSuccess')->nullable()->comment('时间:退款成功');

            $table->timestamp('timeClosed')->nullable()->comment('时间:订单关闭(未支付)');

            $table->unique(['biz', 'bizId']);

        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "pay_order comment '支付订单'");
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
