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
        DB::table('trait_type')->insert([[
            'shortname' => 'TempSet.Mode',
            'gname' => 'action.devices.traits.TemperatureSetting',
            'description' => 'Set the mode of a thermostat device. Different modes might be supported',
            'name' => 'Temperature Setting - Mode',
        ], [
            'shortname' => 'TempSet.Setpoint',
            'gname' => 'action.devices.traits.TemperatureSetting',
            'description' => 'Temperature setpoint of a thermostat device',
            'name' => 'Temperature Setting - Setpoint',
        ], [
            'shortname' => 'TempSet.Ambient',
            'gname' => 'action.devices.traits.TemperatureSetting',
            'description' => 'Observed ambient temperature of a thermostat device',
            'name' => 'Temperature Setting - Ambient',
        ], [
            'shortname' => 'TempSet.Humidity',
            'gname' => 'action.devices.traits.TemperatureSetting',
            'description' => 'Observed ambient humidity of a thermostat device',
            'name' => 'Temperature Setting - Humidity',
        ]]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'TemperatureSetting.Mode')->delete();
        DB::table('trait_type')->where('shortname', 'TemperatureSetting.Setpoint')->delete();
        DB::table('trait_type')->where('shortname', 'TemperatureSetting.Ambient')->delete();
        DB::table('trait_type')->where('shortname', 'TemperatureSetting.Humidity')->delete();
    }
};
