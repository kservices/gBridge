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
        Schema::table('trait_type', function (Blueprint $table) {
            $table->boolean('needsActionTopic')->nullable(false)->default(true);
            $table->boolean('needsStatusTopic')->nullable(false)->default(true);
        });

        DB::table('trait_type')->where('shortname', 'TempSet.Ambient')->update(['needsActionTopic' => false]);
        DB::table('trait_type')->where('shortname', 'TempSet.Humidity')->update(['needsActionTopic' => false]);

        DB::table('trait_type')->where('shortname', 'Scene')->update(['needsStatusTopic' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trait_type', function (Blueprint $table) {
            $table->dropColumn('needsActionTopic');
            $table->dropColumn('needsStatusTopic');
        });
    }
};
