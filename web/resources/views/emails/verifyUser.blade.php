<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Kappelt gBridge</title>
</head>
 
<body>
<h2>Welcome to Kappelt gBridge, {{ $user->email }}!</h2>
<br/>
Please click on the below link to verify your email account
<br/>
<a href="{{ route('profile.verify', $user->verify_token) }}">Verify Email</a>
</body>
 
</html>