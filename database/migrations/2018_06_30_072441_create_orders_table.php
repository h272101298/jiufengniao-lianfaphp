<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('store_id');
            $table->string('number');
            $table->string('group_number');
            $table->string('transaction_id')->nullable();
            $table->string('notify_id')->nullable();
            $table->unsignedInteger('express')->default(0);
            $table->string('express_number')->nullable();
            $table->float('price');
            $table->string('state');
            $table->float('score')->default(0);
            $table->tinyInteger('is_assess')->default(0);
            $table->tinyInteger('delivery')->default(0);
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
        Schema::dropIfExists('orders');
    }
}
