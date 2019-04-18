<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

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

    /**
     * Temporary storing the secret key in memory
     */
    public $secret_key = null;

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->secret_key = base64_encode(Str::random(128));
        $this->key = Hash::make($this->secret_key);

        $this->identifier = Str::random(16);
    }

    public function user(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
