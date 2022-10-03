@extends('emails.email')

@section('mail_content')
<h2>Welcome to Bridge, {{ $user->name }}!</h2>
<br>
Please click on the below link to verify your email account
<br><br>
<a href="{{ route('profile.verify', $user->verify_token) }}">Verify Email</a>



@endsection

@section('mail_reason')
You are receiving this mail because you've registered at Bridge.
@endsection