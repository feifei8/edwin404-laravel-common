<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberMoneyCash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('member_money_cash', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('memberUserId')->nullable()->comment('用户ID');
            /** @see \Edwin404\Member\Types\MemberMoneyCashStatus */
            $table->tinyInteger('status')->nullable()->comment('状态');
            $table->decimal('money', 20, 2)->nullable()->comment('金额');
            $table->decimal('moneyAfterTax', 20, 2)->nullable()->comment('实际到账');
            $table->string('remark', 100)->nullable()->comment('备注');

            /** @see \Edwin404\Member\Types\MemberMoneyCashType */
            $table->tinyInteger('type')->nullable()->comment('提现账号类型');
            $table->string('realname', 50)->nullable()->comment('提现账号姓名');
            $table->string('account', 200)->nullable()->comment('提现账号');

            $table->index(['memberUserId']);

        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "member_money_cash comment '用户提现申请表'");

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
