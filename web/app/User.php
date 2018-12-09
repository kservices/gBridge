<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

use App\Mail\ResetPasswordMail;

class User extends Authenticatable
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
        'email', 'password', 'mqtt_password', 'verify_token', 'name', 'language', 'device_limit'
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
}
