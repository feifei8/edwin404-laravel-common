<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberMoneyCharge extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('member_money_charge', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            /** 20170101+121210+00000000 */
            $table->string('sn', 30)->nullable()->comment('订单号');

            /** @see \Edwin404\Member\Types\MemberMoneyChargeStatus */
            $table->tinyInteger('status')->nullable()->comment('状态');

            $table->integer('memberUserId')->nullable()->comment('用户ID');
            $table->decimal('fee', 20, 2)->nullable()->comment('金额');

            $table->index('memberUserId');
            $table->unique('sn');

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
