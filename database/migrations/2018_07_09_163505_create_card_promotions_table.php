<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('stock_id');
            $table->string('description')->nullable();
            $table->integer('start');
            $table->integer('end');
            $table->integer('number')->default(0);
            $table->float('offer')->default(0);
            $table->integer('clickNum')->default(0);
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
        Schema::dropIfExists('card_promotions');
    }
}
