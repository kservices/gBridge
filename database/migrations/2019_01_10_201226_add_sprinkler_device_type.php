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
        DB::table('device_type')->insert([
            'shortname' => 'Sprinkler',
            'gname' => 'action.devices.types.SPRINKLER',
            'description' => 'Sprinkler',
            'name' => 'Sprinkler',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Sprinkler')->delete();
    }
};
