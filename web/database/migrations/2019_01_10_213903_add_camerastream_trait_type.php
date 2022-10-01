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
            'shortname' => 'CameraStream',
            'gname' => 'action.devices.traits.CameraStream',
            'description' => 'Show a camera stream on a streaming device',
            'name' => 'Camera Stream',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'CameraStream')->delete();
    }
};
