<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Mail\VerifyMail;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //return middleware 'auth' instead in order to disable registration
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validator_messages = [
            'required' => 'Please provide an email and a password!',
            'min' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
            'regex' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
            'accepted' => 'Please accept both our terms and conditions and our privacy policy in order to use this service!',
        ];

        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:user',
            'password' => 'required|string|min:8|regex:/^.*(?=.{5,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#"%§&\/()=?+*~#\'\-_<>,;.:^@]).*$/',
            'accept_toc' => 'accepted',
            'language' => 'required|integer|between:0,1',
        ], $validator_messages);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        //Mosquitto (MQTT server) requires an password string that is unusual, but based on PBKDF2. It must be build manually.
        $mqtt_salt = Str::random(16);
        $mqtt_keypart = base64_encode(hash_pbkdf2('sha256', $data['password'], $mqtt_salt, 902, 24, true));
        $mqtt_key = "PBKDF2\$sha256\$902\$$mqtt_salt\$$mqtt_keypart";

        $cleanName = empty($data['name']) ? $data['email'] : $data['name'];
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'mqtt_password' => $mqtt_key,
            'verify_token' => Str::random(32),
            'language' => $data['language'],
            'name' => $cleanName,
        ]);

        Mail::to($user->email)->send(new VerifyMail($user));

        return $user;
    }

    protected function registered(Request $request, $user)
    {
        $this->guard()->logout();

        return redirect()->route('login')->with('success', 'We sent you an activation code. Check your email and click on the link to verify.');
    }
}
