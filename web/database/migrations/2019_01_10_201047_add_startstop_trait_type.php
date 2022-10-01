<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('trait_type')->insert([
            'shortname' => 'StartStop',
            'gname' => 'action.devices.traits.StartStop',
            'description' => 'Start or stop a generic action',
            'name' => 'Start and Stop',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'StartStop')->delete();
    }
};
