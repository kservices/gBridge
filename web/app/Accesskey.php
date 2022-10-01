<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Accesskey extends Model
{
    /**
     * Model stored in table 'device'
     */
    protected $table = 'google_accesskey';

    /**
     * The primary key is here 'device_id' not the default 'id'
     */
    protected $primaryKey = 'accesskey_id';

    /**
     * Timestamps are unnecessary
     */
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
