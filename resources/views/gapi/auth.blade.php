@extends('layout.app') 

@section('app_content') 

<div class="container">
    <div class="row">
        &nbsp;
    </div>

    @include('common.messages')

    <div class="card-panel green white-text">
        <b>Nice to see you again!</b><br>
        Google is requiring access to your account. @if(Auth::check())Please confirm the linking. @else Please enter your account's credentials. @endif
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
                        <p><span class="card-title">Link Accounts</span></p>
                        @if(Auth::check())
                            {{-- User is already logged in. Just show confirmation button --}}
                            <b>Hi {{ Auth::user()->name }}!</b>
                            <p>Click the button below to link your gBridge account to Google.</p>
                        @else

                        @if ($errors->has('email'))
                        <b class="red-text">{{ $errors->first('email') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="text" class="validate" id="email" name="email" value="{{ old('email') }}" required autofocus>
                            <label for="email">Email/ Username</label>
                        </div>

                        @if ($errors->has('password'))
                        <b class="red-text">{{ $errors->first('password') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="password" class="validate" id="password" name="password" required>
                            <label for="password">Account Password</label>
                        </div>

                        @endif
                    </div>
                    <div class="card-action">
                        <button style="width: 100%;" class="btn waves-effect blue" type="submit">
                            @if(Auth::check())
                            <i class="material-icons left">navigate_next</i>Confirm Linking
                            @else
                            <i class="material-icons left">vpn_key</i>Link Accounts
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection