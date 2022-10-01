<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device', function (Blueprint $table) {
            $table->increments('device_id');
            $table->string('name', 32)->nullable(false);
            $table->unsignedInteger('user_id')->nullable(false);
            $table->foreign('user_id')->references('user_id')->on('user');
            $table->unsignedInteger('devicetype_id')->nullable(false);
            $table->foreign('devicetype_id')->references('devicetype_id')->on('device_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device');
    }
};
