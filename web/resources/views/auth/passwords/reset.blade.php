@extends('layout.app')

@section('customHead')
<meta name="referrer" content="no-referrer">
@endsection

@section('app_content')

<div class="container">
    <div class="row">
        &nbsp;
    </div>
    <div class="row">
        <div class="col s12 m12 l8 offset-l2">
            <div class="card blue-grey lighten-4 black-text">
                <form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('password.request') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="card-content black-text">
                        <p><span class="card-title">Reset Password</span></p>
                        @if ($errors->has('email'))
                        <b class="red-text">{{ $errors->first('email') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="email" class="validate" id="email" name="email" value="{{ $email or old('email') }}" required autofocus>
                            <label for="email">Email</label>
                        </div>

                        @if ($errors->has('password'))
                        <b class="red-text">{{ $errors->first('password') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="password" class="validate" id="password-confirm" name="password_confirmation" required>
                            <label for="password-confirm">Confirm Password</label>
                        </div>

                        @if ($errors->has('password_confirmation'))
                        <b class="red-text">{{ $errors->first('password_confirmation') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="password" class="validate" id="password-confirm" name="password" required>
                            <label for="password">Password</label>
                        </div>


                        <label class="right">
                            <a class="blue-text" href="{{ route('login') }}">
                                <b>To Login</b>
                            </a>
                        </label>
                    </div>
                    <div class="card-action">
                        <button style="width: 100%;" class="btn waves-effect blue" type="submit">
                            <i class="material-icons left">vpn_key</i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
