<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScoreProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('score_products', function (Blueprint $table) {
            $table->increments('id');
            //$table->increments('id');
            $table->unsignedInteger('store_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->longText('detail')->nullable();
            //$table->smallInteger('brokerage')->default(0);
            $table->unsignedInteger('express')->default(0);
            $table->float('express_price')->default(0);
            $table->string('share_title')->nullable();
            $table->string('share_detail')->nullable();
            $table->string('norm')->default('fixed');
            $table->tinyInteger('state')->default(0);
            $table->tinyInteger('review')->default(0);
            $table->tinyInteger('deleted')->default(0);
            $table->tinyInteger('hot')->default(0);
//            $table->unsignedInteger('type_id')->default(0);
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
        Schema::dropIfExists('score_products');
    }
}
