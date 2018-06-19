<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Mail\VerifyMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;

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
            'confirmed' => 'The given passwords do not match',
            'min' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
            'regex' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
        ];
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:user',
            'password' => 'required|string|min:8|confirmed|regex:/^.*(?=.{5,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#"%§&\/()=?+*~#\'\-_<>,;.:^]).*$/',
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
        $user = User::create([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'verify_token' => str_random(32),
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
