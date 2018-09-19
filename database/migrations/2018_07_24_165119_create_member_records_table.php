<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('level_id');
            $table->string('name');
            $table->float('price',8,2);
            $table->string('state')->default('created');
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
        Schema::dropIfExists('member_records');
    }
}
