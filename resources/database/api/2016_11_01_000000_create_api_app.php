<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiApp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('api_app', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('name', 50)->nullable()->comment('应用名称');
            $table->string('appId', 32)->nullable()->comment('AppId');
            $table->string('appSecret', 32)->nullable()->comment('AppSecret');

            $table->tinyInteger('moduleXxx')->nullable()->comment('功能');

            $table->unique(['appId']);

        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "api_app comment 'API App'");

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
