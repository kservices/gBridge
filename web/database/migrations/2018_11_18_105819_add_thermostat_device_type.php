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
            'shortname' => 'Thermostat',
            'gname' => 'action.devices.types.THERMOSTAT',
            'description' => 'Thermostat device',
            'name' => 'Thermostat',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Thermostat')->delete();
    }
};
