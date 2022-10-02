@extends('emails.email')

@section('mail_content')
<h2>Welcome to Kappelt gBridge, {{ $user->name }}!</h2>
<br>
Please click on the below link to verify your email account
<br><br>
<a href="{{ route('profile.verify', $user->verify_token) }}">Verify Email</a>

@if(env('KSERVICES_HOSTED', false))
<p>
    <b>You might find these resources useful: </b>
    <ul>
        <li>Need to get help? Join our community forum: <a href="https://community.gbridge.io">https://community.gbridge.io</a></li>
        <li>Get started quickly? Read the gBridge docs: <a href="https://doc.gbridge.io">https://doc.gbridge.io</a></li>
        <li>Want to stay up-to-date? Follow us on Twitter: <a href="https://twitter.com/Kappelt_gBridge">https://twitter.com/Kappelt_gBridge</a></li>
        <li>Stay informed about maintenance? Look at our statuspages: <a href="https://status.gbridge.io">https://status.gbridge.io</a></li>
    </ul>
</p>
@endif

@endsection

@section('mail_reason')
You are receiving this mail because you've registered at Kappelt gBridge.
@endsection