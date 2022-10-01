<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    /**
     * Model stored in table 'device_type'
     */
    protected $table = 'device_type';

    /**
     * The primary key is here 'devicetype_id' not the default 'id'
     */
    protected $primaryKey = 'devicetype_id';

    /**
     * Timestamps are unnecessary
     */
    public $timestamps = false;

    public function devices()
    {
        return $this->hasMany(\App\Device::class, 'devicetype_id');
    }
}
