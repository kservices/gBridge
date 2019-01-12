<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDishwasherDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_type')->insert([
            'shortname' => 'Dishwasher',
            'gname' => 'action.devices.types.DISHWASHER',
            'description' => 'Dishwashers',
            'name' => 'Dishwasher'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Dishwasher')->delete();
    }
}
