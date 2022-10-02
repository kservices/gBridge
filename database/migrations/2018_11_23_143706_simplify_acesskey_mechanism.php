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
        DB::table('google_accesskey')->where('password_used', '=', false)->delete();

        Schema::table('google_accesskey', function (Blueprint $table) {
            $table->dropColumn('password');
            $table->dropColumn('password_used');
            $table->dropColumn('used_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_accesskey', function (Blueprint $table) {
            $table->string('password', 16)->nullable(true);
            $table->boolean('password_used')->nullable(false)->default(true);
            $table->timestamp('used_at')->nullable(true);
        });
    }
};
