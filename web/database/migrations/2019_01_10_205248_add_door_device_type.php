<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDoorDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Door',
            'gname' => 'action.devices.types.DOOR',
            'description' => 'Door',
            'name' => 'Door'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Door')->delete();
    }
}
