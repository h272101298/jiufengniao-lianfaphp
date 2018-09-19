<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockSnapshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id')->default(0);
            $table->unsignedInteger('stock_id')->default(0);
            $table->unsignedInteger('store_id')->default(0);
            $table->string('cover');
            $table->string('name');
            $table->string('detail');
            $table->string('product');
            $table->float('price');
            $table->integer('number');
            $table->float('score')->default(0);
            $table->string('assess')->nullable();
            $table->tinyInteger('is_assess')->nullable();
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
        Schema::dropIfExists('stock_snapshots');
    }
}
