<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

class UserProfileController extends Controller
{
    /**
     * Show the user's panel.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('profile.profile', [
            'site_title' => 'My Account',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updatepwd(Request $request, User $user)
    {
        $validator_messages = [
            'required' => 'Both the current password and the new password are required!',
            'confirmed' => 'The given passwords do not match',
            'min' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
            'regex' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
        ];
        $validator = Validator::make($request->all(), [
            'password' => 'bail|required',
            'newpassword' => 'bail|required|min:8|confirmed|regex:/^.*(?=.{5,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#"%§&\/()=?+*~#\'\-_<>,;.:^]).*$/',
        ], $validator_messages)->validate();

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return redirect()->route('profile.index')->with('error', 'Your current password was wrong!');
        }

        $edituser = Auth::user();
        $edituser->password = Hash::make($request->input('newpassword'));
        $edituser->save();

        return redirect()->route('profile.index')->with('success', 'The password has been changed.');
    }

    /**
     * Update the user's display name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updatename(Request $request, User $user)
    {
        $validator_messages = [
            'required' => 'Your display name cannot be empty!',
        ];
        $validator = Validator::make($request->all(), [
            'name' => 'bail|required',
        ], $validator_messages)->validate();

        $edituser = Auth::user();
        $edituser->name = $request->input('name');
        $edituser->save();

        return redirect()->route('profile.index')->with('success', 'Your Display Name has been changed!.');
    }

    /**
     * Update the user's prefered language.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updatelang(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|integer|between:0,1',
        ])->validate();

        $edituser = Auth::user();
        $edituser->language = $request->input('language');
        $edituser->save();

        return redirect()->route('profile.index')->with('success', 'Your prefered language has been changed!.');
    }

    /**
     * Update the user's mqtt server password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updatemqtt(Request $request, User $user)
    {
        $validator_messages = [
            'required' => 'Both the current password and the new password are required!',
            'confirmed' => 'The given passwords do not match',
            'min' => 'The password must be at least 8 characters long.',
            'regex' => 'The password must be at least 8 characters long and must contain at least one number (0-9), letters, and at least one special char.',
        ];
        $validator = Validator::make($request->all(), [
            'account-password' => 'bail|required',
            'mqtt-password' => 'bail|required|min:8|confirmed',
        ], $validator_messages)->validate();

        if (! Hash::check($request->input('account-password'), Auth::user()->password)) {
            return redirect()->route('profile.index')->with('error', 'Your current password was wrong!');
        }

        //Mosquitto (MQTT server) requires an password string that is unusual, but based on PBKDF2. It must be build manually.
        $salt = str_random(16);
        $key = base64_encode(hash_pbkdf2('sha256', $request->input('mqtt-password'), $salt, 902, 24, true));
        $mqtt_key = "PBKDF2\$sha256\$902\$$salt\$$key";

        $edituser = Auth::user();
        $edituser->mqtt_password = $mqtt_key;
        $edituser->save();

        return redirect()->route('profile.index')->with('success', 'Your new MQTT password has been set.');
    }

    /**
     * Handle the verification-link that the user has received after registering
     *
     * @param $verify_token Verification-Token
     */
    public function verify($verify_token)
    {
        $user = User::where('verify_token', $verify_token)->first();
        if (isset($user)) {
            $user->verify_token = null;
            $user->save();

            return redirect()->route('login')->with('success', 'Your account has been verified, you can log in now.');
        } else {
            return redirect()->route('login')->with('error', 'Your account has already been verified!');
        }
    }
}
