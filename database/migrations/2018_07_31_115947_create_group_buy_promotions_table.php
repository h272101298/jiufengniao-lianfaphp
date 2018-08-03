<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupBuyPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_buy_promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->unsignedInteger('product_id');
//            $table->unsignedInteger('stock_id');
            $table->string('title');
//            $table->integer('number')->default(0);
            $table->integer('time');
//            $table->integer('start');
//            $table->integer('end');
            $table->integer('people_number');
//            $table->float('origin_price',8,2)->default(0);
//            $table->float('price',8,2)->default(0);
            $table->tinyInteger('free')->default(0);
            $table->tinyInteger('hot')->default(0);
            $table->tinyInteger('state')->default(1);
            $table->tinyInteger('enable')->default(0);
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
        Schema::dropIfExists('group_buy_promotions');
    }
}
