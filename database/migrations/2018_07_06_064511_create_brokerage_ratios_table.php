<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrokerageRatiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brokerage_ratios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('system')->default(20);
            $table->integer('level1')->default(50);
            $table->integer('level2')->default(30);
            $table->integer('level3')->default(20);
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
        Schema::dropIfExists('brokerage_ratios');
    }
}
