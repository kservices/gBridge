<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFirstUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = readline("Enter your E-Mail address: ");
        print("A new user with the mail \"$user\" and the password \"123456\" has been created.\nPlease change the password immediately!\n\n\n");

        DB::table('user')->insert([
            'user_id' => 1,
            'email' => $user,
            'password' => bcrypt('123456'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('user')->where('user_id', 1)->delete();
    }
}
