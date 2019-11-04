<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportCountDaily extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('report_count_daily', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('tableName', 100)->nullable()->comment('表');
            $table->string('tableWhere', 200)->nullable()->comment('条件');
            $table->date('day')->nullable()->comment('日期');
            $table->integer('cnt')->nullable()->comment('数量');

            $table->unique(['tableName', 'tableWhere', 'day']);

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
