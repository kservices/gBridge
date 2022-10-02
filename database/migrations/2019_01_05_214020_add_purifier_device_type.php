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
            'shortname' => 'Purifier',
            'gname' => 'action.devices.types.AIRPURIFIER',
            'description' => 'Air purifying devices',
            'name' => 'Air Purifier',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Purifier')->delete();
    }
};
