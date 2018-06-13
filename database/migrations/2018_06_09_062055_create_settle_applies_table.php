<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettleAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settle_applies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->commit('姓名');
            $table->string('phone')->commit('联系方式');
            $table->unsignedInteger('city')->commit('城市id');
            $table->string('storeName')->unique()->commit('店铺名');
            $table->unsignedInteger('type')->commit('申请类型');
            $table->string('category')->commit('主营类目');
            $table->tinyInteger('state')->default(0)->commit('状态');
            $table->string('notifyId')->nullable()->commit('通知ID');
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
        Schema::dropIfExists('settle_applies');
    }
}
