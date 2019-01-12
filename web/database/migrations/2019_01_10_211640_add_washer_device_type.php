<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWasherDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Washer',
            'gname' => 'action.devices.types.WASHER',
            'description' => 'Washing machines',
            'name' => 'Washer'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Washer')->delete();
    }
}
