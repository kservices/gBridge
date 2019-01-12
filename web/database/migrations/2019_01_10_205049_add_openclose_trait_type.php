<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpencloseTraitType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('trait_type')->insert([
            'shortname' => 'OpenClose',
            'gname' => 'action.devices.traits.OpenClose',
            'description' => 'Open or close devices to a certain percentage',
            'name' => 'Open and Close'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trait_type')->where('shortname', 'OpenClose')->delete();
    }
}
