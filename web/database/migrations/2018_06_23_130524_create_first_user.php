<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = readline('Enter your E-Mail address: ');
        echo "A new user with the mail \"$user\" and the password \"123456\" has been created.\nPlease change the password immediately!\n\n\n";

        DB::table('user')->insert([
            'user_id' => 1,
            'email' => $user,
            'password' => Hash::make('123456'),
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
};
