<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrizeConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prize_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('share_score')->default(0);
            $table->unsignedInteger('register_score')->default(0);
            $table->unsignedInteger('prize_score')->default(0);
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
        Schema::dropIfExists('prize_configs');
    }
}
