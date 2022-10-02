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
        Schema::create('api_key', function (Blueprint $table) {
            $table->increments('apikey_id');

            $table->unsignedInteger('user_id')->nullable(false);
            $table->foreign('user_id')->references('user_id')->on('user');

            $table->string('key', 255);
            $table->boolean('privilege_user')->default(false);

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
        Schema::dropIfExists('api_key');
    }
};
