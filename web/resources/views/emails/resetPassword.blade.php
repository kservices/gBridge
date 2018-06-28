<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Kappelt gBridge</title>
</head>
 
<body>
<h2>Hi!</h2>
<br/>
You are receiving this email because we received a password reset request for your account. To do so, click the link below:
<br/><br/>
<a href="{{ route('password.reset', $token) }}">{{ route('password.reset', $token) }}</a>
</body>
 
</html>