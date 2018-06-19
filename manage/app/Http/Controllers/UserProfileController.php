<?php

namespace App\Http\Controllers;

use Validator;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        if(!Hash::check($request->input('password'), Auth::user()->password)){
            return redirect()->route('profile.index')->with('error', 'Your current password was wrong!');
        }

        $edituser = Auth::user();
        $edituser->password = Hash::make($request->input('newpassword'));
        $edituser->save();

        return redirect()->route('profile.index')->with('success', 'The password has been changed.');
    }

    /**
     * Handle the verification-link that the user has received after registering
     * @param $verify_token Verification-Token
     */
    public function verify($verify_token){
        $user = User::where('verify_token', $verify_token)->first();
        if(isset($user)){
            $user->verify_token = NULL;
            $user->save();
            return redirect()->route('login')->with('success', 'Your account has been verified, you can log in now.');
        }else{
            return redirect()->route('login')->with('error', 'Your account has already been verified!');
        }
    }

}
