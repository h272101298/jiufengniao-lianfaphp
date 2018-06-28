<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->string('name');
            $table->text('detail');
            $table->smallInteger('brokerage')->default(0);
            $table->unsignedInteger('express')->default(0);
            $table->float('express_price')->default(0);
            $table->string('share_title')->nullable();
            $table->string('share_detail')->nullable();
            $table->unsignedInteger('type_id')->default(0);
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
        Schema::dropIfExists('products');
    }
}
