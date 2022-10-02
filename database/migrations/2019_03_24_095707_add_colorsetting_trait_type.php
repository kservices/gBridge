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
            'shortname' => 'ColorSettingRGB',
            'gname' => 'action.devices.traits.ColorSetting',
            'description' => 'Set a light to a specific color (RGB data formatting)',
            'name' => 'Color Setting (RGB)',
        ]);
        DB::table('trait_type')->insert([
            'shortname' => 'ColorSettingJSON',
            'gname' => 'action.devices.traits.ColorSetting',
            'description' => 'Set a light to a specific color (JSON data encoding)',
            'name' => 'Color Setting (JSON)',
        ]);
        DB::table('trait_type')->insert([
            'shortname' => 'ColorSettingTemp',
            'gname' => 'action.devices.traits.ColorSetting',
            'description' => 'Set a light to a specific color (Color Temperature)',
            'name' => 'Color Setting (Temperature)',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'ColorSettingRGB')->delete();
        DB::table('trait_type')->where('shortname', 'ColorSettingJSON')->delete();
        DB::table('trait_type')->where('shortname', 'ColorSettingTemp')->delete();
    }
};
