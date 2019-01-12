<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFanspeedTraitType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('trait_type')->insert([
            'shortname' => 'FanSpeed',
            'gname' => 'action.devices.traits.FanSpeed',
            'description' => 'Control the speed of a fan',
            'name' => 'Fan Speed'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'FanSpeed')->delete();
    }
}
