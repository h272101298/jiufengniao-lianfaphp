<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreWithdrawsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_withdraws', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->float('price',18,2)->default(0);
            $table->string('remark')->nullable();
            $table->string('bank');
            $table->string('account');
            $table->tinyInteger('state')->default(0);
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
        Schema::dropIfExists('store_withdraws');
    }
}
