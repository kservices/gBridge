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
        Schema::table('device', function (Blueprint $table) {
            $table->dropForeign('device_user_id_foreign');
            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
        });
        Schema::table('google_accesskey', function (Blueprint $table) {
            $table->dropForeign('google_accesskey_user_id_foreign');
            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device', function (Blueprint $table) {
            $table->dropForeign('device_user_id_foreign');
            $table->foreign('user_id')->references('user_id')->on('user');
        });
        Schema::table('google_accesskey', function (Blueprint $table) {
            $table->dropForeign('google_accesskey_user_id_foreign');
            $table->foreign('user_id')->references('user_id')->on('user');
        });
    }
};
