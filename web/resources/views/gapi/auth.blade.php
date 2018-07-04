@extends('layout.app') 

@section('app_content') 

<div class="container">
    <div class="row">
        &nbsp;
    </div>

    @include('common.messages')

    <div class="card-panel green white-text">
        <b>Nice to see you again!</b><br>
        Google is requiring access to your gBridge account. Please enter your account's email and an accesskey you've generated.
    </div>

    <div class="row">
        <div class="col s12 m12 l8 offset-l2">
            <div class="card blue-grey lighten-4 black-text">
                <form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('gapi.checkauth') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="client_id" value="{{ $client_id }}">
                    <input type="hidden" name="response_type" value="{{ $response_type }}">
                    <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
                    <input type="hidden" name="state" value="{{ $state }}">
                    <div class="card-content black-text">
                        <p><span class="card-title">Login</span></p>
                        @if ($errors->has('email'))
                        <b class="red-text">{{ $errors->first('email') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="email" class="validate" id="email" name="email" value="{{ old('email') }}" required autofocus>
                            <label for="email">Email</label>
                        </div>

                        @if ($errors->has('password'))
                        <b class="red-text">{{ $errors->first('password') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="password" class="validate tooltipped" id="accesskey" name="accesskey" required data-tooltip="This is not your account's password!<br>You can create a temporary accesskey in your account's dashboard">
                            <label for="accesskey">Accesskey</label>
                        </div>
                    </div>
                    <div class="card-action">
                        <button style="width: 100%;" class="btn waves-effect blue" type="submit">
                            <i class="material-icons left">vpn_key</i>Authenticate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection