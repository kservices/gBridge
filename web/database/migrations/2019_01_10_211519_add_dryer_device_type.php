<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDryerDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Dryer',
            'gname' => 'action.devices.types.DRYER',
            'description' => 'Dryer',
            'name' => 'Dryer'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Dryer')->delete();
    }
}
