@extends('emails.email')

@section('mail_content')
<h2>Hi!</h2>
<br>
You are receiving this email because we received a password reset request for your account. To do so, click the link below:
<br><br>
<a href="{{ route('password.reset', $token) }}">{{ route('password.reset', $token) }}</a>
@endsection

@section('mail_reason')
You are receiving this mail because you've requested to reset your password for Kappelt gBridge.
@endsection