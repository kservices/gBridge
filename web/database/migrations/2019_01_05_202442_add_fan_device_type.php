<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFanDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('device_type', function (Blueprint $table) {
            DB::table('device_type')->insert([
                'shortname' => 'Fan',
                'gname' => 'action.devices.types.FAN',
                'description' => 'Control a fan',
                'name' => 'Fan'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'Fan')->delete();
    }
}
