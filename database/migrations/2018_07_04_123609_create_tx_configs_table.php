<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTxConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tx_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id');
            $table->string('app_secret');
            $table->string('api_key');
            $table->string('mch_id');
            $table->string('ssl_cert');
            $table->string('ssl_key');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tx_configs');
    }
}
