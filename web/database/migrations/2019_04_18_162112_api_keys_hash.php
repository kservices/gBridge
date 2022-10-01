<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->string('identifier', 255)->unique()->nullable();
        });

        $rows = DB::table('api_key')->get(['apikey_id', 'key']);
        foreach ($rows as $row) {
            $secretKey = $row->key;

            DB::table('api_key')
                ->where('apikey_id', $row->apikey_id)
                ->update([
                    'key' => Hash::make(substr($secretKey, 4)),
                    'identifier' => substr($secretKey, 0, 4),
                ]);
        }

        Schema::table('api_key', function (Blueprint $table) {
            $table->string('identifier', 255)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $msg = 'This migration cannot be undone. Reverting this migration is going to break all API keys';
        echo "Error: $msg\n";
        Log::error($msg);

        Schema::table('api_key', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
    }
};
