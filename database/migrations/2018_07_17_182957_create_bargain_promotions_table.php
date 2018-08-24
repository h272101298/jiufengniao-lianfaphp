<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBargainPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bargain_promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->unsignedInteger('product_id');
            //$table->unsignedInteger('stock_id');
            //$table->float('min_price',10,2);
            //$table->float('origin_price',10,2);
            $table->unsignedInteger('clickNum');
            //$table->integer('start');
            //$table->integer('end');
            $table->integer('time')->default(0);
            $table->string('description');
            $table->integer('number');
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
        Schema::dropIfExists('bargain_promotions');
    }
}
