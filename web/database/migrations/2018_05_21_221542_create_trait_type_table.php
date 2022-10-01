<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraitTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trait_type', function (Blueprint $table) {
            $table->increments('traittype_id');
            $table->string('shortname', 16)->nullable(false);
            $table->string('gname', 64)->nullable(false);
            $table->string('description', 128)->nullable(false);
            $table->string('name', 32)->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trait_type');
    }
}
