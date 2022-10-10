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
            $table->enum('mqttStatusPayloadType', ['text', 'json'])->after('mqttStatusTopic');
            $table->string('mqttStatusPayloadKey')->nullable()->after('mqttStatusPayloadType');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trait', function (Blueprint $table) {
            $table->dropColumn('mqttStatusPayloadType');
            $table->dropColumn('mqttStatusPayloadKey');
        });
    }
};
