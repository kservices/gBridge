<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    /**
     * Model stored in table 'apikey'
     */
    protected $table = 'api_key';
    /**
     * The primary key is here 'apikey_id' not the default 'id'
     */
    protected $primaryKey = 'apikey_id';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->key = base64_encode(Str::random(128));
    }

    public function user(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
