<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /**
     * Model stored in table 'device'
     */
    protected $table = 'device';

    /**
     * The primary key is here 'device_id' not the default 'id'
     */
    protected $primaryKey = 'device_id';

    /**
     * Timestamps are unnecessary
     */
    public $timestamps = false;

    /**
     * Tables that are always joined
     * Not necessary? Don't unterstand how that works :)
     */
    //public $with = ['deviceType', 'traits'];

    public function deviceType()
    {
        return $this->belongsTo(\App\Models\DeviceType::class, 'devicetype_id');
    }

    public function traits()
    {
        //This is a m:n relation, joined by the table trait
        return $this->belongsToMany(\App\Models\TraitType::class, 'trait', 'device_id', 'traittype_id')->withPivot(
            'trait_id', 'config', 'mqttActionTopic', 'mqttStatusTopic', 'mqttStatusPayloadType', 'mqttStatusPayloadKey'
        );
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Returns this device as an object compliant with the gBridge API V2 Spec
     */
    public function toApiV2Object($userid)
    {
        $this->fresh();

        $traits = $this->traits->map(function ($trait) use ($userid) {
            return $trait->toApiV2Object($userid);
        });

        return [
            'id' => $this->device_id,
            'name' => $this->name,
            'type' => $this->deviceType->shortname,
            'traits' => $traits,
            'twofa' => $this->twofa_type,
            'twofaPin' => $this->twofa_pin,
        ];
    }
}
