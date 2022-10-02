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
            'shortname' => 'AC',
            'gname' => 'action.devices.types.AC_UNIT',
            'description' => 'Control an air conditioning unit',
            'name' => 'Air Conditioner',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('device_type')->where('shortname', 'AC')->delete();
    }
};
