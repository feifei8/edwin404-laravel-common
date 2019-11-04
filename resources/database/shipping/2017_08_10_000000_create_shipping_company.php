<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippingCompany extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_company', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('sort')->nullable()->comment('排序');
            $table->tinyInteger('active')->nullable()->comment('启用');
            $table->string('code', 100)->nullable()->comment('代码');
            $table->string('name', 100)->nullable()->comment('名称');

            $table->index(['sort']);

        });

        $data = [
            ['sort' => 999, 'active' => true, 'code' => 'shunfeng', 'name' => '顺丰快递'],
            ['sort' => 999, 'active' => true, 'code' => 'yuantong', 'name' => '圆通快递'],
            ['sort' => 999, 'active' => true, 'code' => 'shentong', 'name' => '申通快递'],
            ['sort' => 999, 'active' => true, 'code' => 'yunda', 'name' => '韵达快递'],
        ];
        \Illuminate\Support\Facades\DB::table('shipping_company')->insert($data);

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
