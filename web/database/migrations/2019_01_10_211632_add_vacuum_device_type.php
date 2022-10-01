<?php

use Illuminate\Database\Migrations\Migration;

class AddVacuumDeviceType extends Migration
{
    /**
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Vacuum',
            'gname' => 'action.devices.types.VACUUM',
            'description' => 'Vacuum cleaner',
            'name' => 'Vacuum',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Vacuum')->delete();
    }
}
