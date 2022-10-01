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
        Schema::table('trait', function (Blueprint $table) {
            $table->string('mqttActionTopic')->nullable(false);
            $table->string('mqttStatusTopic')->nullable(false);
        });
        \DB::statement("UPDATE trait INNER JOIN device on trait.device_id = device.device_id INNER JOIN trait_type on trait.traittype_id = trait_type.traittype_id SET trait.mqttActionTopic = CONCAT('d', trait.device_id, '/', LOWER(trait_type.shortname))");
        \DB::statement("UPDATE trait INNER JOIN device on trait.device_id = device.device_id INNER JOIN trait_type on trait.traittype_id = trait_type.traittype_id SET trait.mqttStatusTopic = CONCAT('d', trait.device_id, '/', LOWER(trait_type.shortname), '/set')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trait', function (Blueprint $table) {
            $table->dropColumn('mqttActionTopic');
            $table->dropColumn('mqttStatusTopic');
        });
    }
};
