<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyTraitConfigColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trait', function (Blueprint $table) {
            $table->dropColumn('config');
        });
        Schema::table('trait', function (Blueprint $table) {
            $table->json('config')->nullable(true);
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
            $table->dropColumn('config');
        });
        Schema::table('trait', function (Blueprint $table) {
            $table->string('config', 128)->default('');
        });
    }
}
