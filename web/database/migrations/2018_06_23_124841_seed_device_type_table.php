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
            [
                'devicetype_id' => 1,
                'shortname' => 'Light',
                'gname' => 'action.devices.types.LIGHT',
                'description' => 'Lightbulb',
                'name' => 'Light',
            ], [
                'devicetype_id' => 2,
                'shortname' => 'Outlet',
                'gname' => 'action.devices.types.OUTLET',
                'description' => 'Switchable Outlet',
                'name' => 'Outlet',
            ], [
                'devicetype_id' => 3,
                'shortname' => 'Switch',
                'gname' => 'action.devices.types.SWITCH',
                'description' => 'General purpose switching device',
                'name' => 'Switch',
            ], ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device')->where('devicetype_id', 1)->delete();
        DB::table('device')->where('devicetype_id', 2)->delete();
        DB::table('device')->where('devicetype_id', 3)->delete();
        DB::table('device_type')->where('devicetype_id', 1)->delete();
        DB::table('device_type')->where('devicetype_id', 2)->delete();
        DB::table('device_type')->where('devicetype_id', 3)->delete();
    }
};
