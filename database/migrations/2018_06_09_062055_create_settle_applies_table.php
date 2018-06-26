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
            $table->string('city')->commit('城市');
            $table->string('storeName')->unique()->commit('店铺名');
            $table->string('type')->commit('申请类型');
            $table->string('category')->commit('主营类目');
//            $table->tinyInteger('state')->default(0)->commit('状态');
            $table->string('notifyId')->nullable()->commit('通知ID');
            $table->string('picture')->nullable()->commit('执照');
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
