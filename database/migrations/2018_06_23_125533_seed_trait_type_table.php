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
            [
                'traittype_id' => 1,
                'shortname' => 'OnOff',
                'gname' => 'action.devices.traits.OnOff',
                'description' => 'Turn a device on or off',
                'name' => 'On and Off',
            ], [
                'traittype_id' => 2,
                'shortname' => 'Brightness',
                'gname' => 'action.devices.traits.Brightness',
                'description' => 'Set a brightness for this device from 0 to 100 %',
                'name' => 'Brightness',
            ], ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait')->where('traittype_id', 1)->delete();
        DB::table('trait')->where('traittype_id', 2)->delete();
        DB::table('trait_type')->where('traittype_id', 1)->delete();
        DB::table('trait_type')->where('traittype_id', 2)->delete();
    }
};
