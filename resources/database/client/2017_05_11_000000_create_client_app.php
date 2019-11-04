<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientApp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('client_app', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->string('name', 50)->nullable()->comment('应用名称');
            $table->string('appId', 32)->nullable()->comment('AppId');
            $table->string('appSecret', 32)->nullable()->comment('AppSecret');

            $table->tinyInteger('moduleXxx')->nullable()->comment('功能');

            $table->unique(['appId']);

        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE " . env('DB_PREFIX') . "client_app comment 'Client App'");

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
