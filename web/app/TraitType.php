<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TraitType extends Model
{
    /**
     * Model stored in table 'trait_type'
     */
    protected $table = 'trait_type';
    /**
     * The primary key is here 'traittype_id' not the default 'id'
     */
    protected $primaryKey = 'traittype_id';
    /**
     * Timestamps are unnecessary
     */
    public $timestamps = false;

    public function devices(){
        //This is a m:n relation, joined by the table trait
        return $this->belongsToMany('App\Device', 'trait', 'traittype_id', 'device_id')->withPivot('config');
    }
}
