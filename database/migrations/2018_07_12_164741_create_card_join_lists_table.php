<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardJoinListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_join_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('promotion_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('founder_id');
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
        Schema::dropIfExists('card_join_lists');
    }
}
