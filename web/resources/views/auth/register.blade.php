@extends('layout.app')

@section('app_content')

<div class="container">
    <div class="row">
        &nbsp;
    </div>

    @include('common.messages')

    <div class="row">
        <div class="col s12 m12 l8 offset-l2">
            <div class="card blue-grey lighten-4 black-text">
                <form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('register') }}">
                    {{ csrf_field() }}
                    <div class="card-content black-text">
                        <p><span class="card-title">Register</span></p>

                        @if ($errors->has('name'))
                        <b class="red-text">{{ $errors->first('name') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="text" class="validate" id="name" name="name" value="{{ old('name') }}" autofocus>
                            <label for="name">Name</label>
                        </div>

                        @if ($errors->has('email'))
                        <b class="red-text">{{ $errors->first('email') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="email" class="validate" id="email" name="email" value="{{ old('email') }}" required>
                            <label for="email">Email</label>
                        </div>

                        @if ($errors->has('language'))
                        <b class="red-text">{{ $errors->first('language') }}</b>
                        @endif
                        <div class="input-field">
                            <select class="validate" id="language" name="language">
                                <option value="0"{{ (old('language') == 0) ? ' selected':'' }}>English</option>
                                <option value="1"{{ (old('language') == 1) ? ' selected':'' }}>Deutsch</option>
                            </select>
                            <label>Prefered Language</label>
                        </div>

                        @if ($errors->has('password'))
                        <b class="red-text">{{ $errors->first('password') }}</b>
                        @endif
                        <div class="input-field">
                            <input type="password" class="validate" id="password" name="password" required>
                            <label for="password">Password</label>
                        </div>

                        @if(env('KSERVICES_HOSTED', false))
                        <label>
                            <input type="checkbox" id="accept_toc" name="accept_toc">
                            <span>I've read and accepted both the <a href="https://about.gbridge.kappelt.net/toc" target="_blank">Terms and Conditions</a> and the <a href="https://about.gbridge.kappelt.net/privacy" target="_blank">Privacy Policy</a></span>
                        </label>
                        @else
                        <input type="hidden" name="accept_toc" value="on">
                        @endif

                    </div>
                    <div class="card-action">
                        <button style="width: 100%;" class="btn waves-effect blue" type="submit">
                            <i class="material-icons left">vpn_key</i>Register
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
