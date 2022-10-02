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
        Schema::create('trait', function (Blueprint $table) {
            $table->increments('trait_id');
            $table->string('config', 128)->default('');
            $table->unsignedInteger('traittype_id')->nullable(false);
            $table->foreign('traittype_id')->references('traittype_id')->on('trait_type');
            $table->unsignedInteger('device_id')->nullable(false);
            $table->foreign('device_id')->references('device_id')->on('device')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trait');
    }
};
