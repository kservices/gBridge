<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function user(){
        return $this->belongsTo('App\User', 'user_id');
    }
    
    /**
     * Returns true, if this key has been created more than one hour ago
     */
    public function isExpired(){
        return (Carbon::now('Europe/Berlin') > Carbon::parse($this->generated_at)->addHours(1));
    }
}
