<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adverts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->nullable();
            $table->string('pic')->commit('图片');
            $table->string('detail')->nullable();
            $table->unsignedInteger('product_id')->default(0);
//            $table->string('category_id')->commit('位置');
            $table->tinyInteger('type')->default(1)->commit('类型');
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
        Schema::dropIfExists('adverts');
    }
}
