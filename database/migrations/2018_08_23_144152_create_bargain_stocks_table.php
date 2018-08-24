<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBargainStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bargain_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bargain_id');
            $table->unsignedInteger('stock_id');
            $table->double('origin_price',10,2);
            $table->double('min_price',10,2);
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
        Schema::dropIfExists('bargain_stocks');
    }
}
