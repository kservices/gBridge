<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBlindsDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Blinds',
            'gname' => 'action.devices.types.BLINDS',
            'description' => 'Blinds',
            'name' => 'Blinds'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Blinds')->delete();
    }
}
