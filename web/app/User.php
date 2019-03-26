<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

use App\Mail\ResetPasswordMail;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * Model stored in table 'user'
     */
    protected $table = 'user';
    /**
     * The primary key is here 'user_id' not the default 'id'
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'login_username', 'password', 'mqtt_password', 'verify_token', 'name', 'language', 'device_limit'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'mqtt_password',
    ];

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this)->send(new ResetPasswordMail($token, $this));
    }

    public function devices(){
        return $this->hasMany('App\Device', 'user_id');
    }

    public function accesskeys(){
        return $this->hasMany('App\Accesskey', 'user_id');
    }

    public function apiKeys(){
        return $this->hasMany('App\ApiKey', 'user_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
