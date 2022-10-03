@extends('layout.app')

@section('app_content')

    <div class="container">
		{{-- the user dropdown menu --}}
		<ul id="user-dropdown" class="dropdown-content">
			<li class="{{ Request::is('profile*') ? 'active':'' }}"><a href="{{ route('profile.index') }}">My Account</a></li>
			<li class="{{ Request::is('apikey*') ? 'active':'' }}"><a href="{{ route('apikey.index') }}">API Keys</a></li>
			<li class="divider"></li>
			<li><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a></li>
		</ul>
		{{-- virtual logout form --}}
		<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
			{{ csrf_field() }}
		</form>

		<nav class="nav-extended blue">
			<div class="nav-wrapper">
				<a href="{{ route('device.index') }}" class="brand-logo" style="padding: 7px 20px 7px 20px;">Bridge</a>
				<a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>
				<ul id="nav-mobile" class="right hide-on-med-and-down">
					<li class="{{ Request::is('device*') ? 'active':'' }}"><a href="{{ route('device.index') }}">Devices</a></li>
					<li class="{{ Request::is('accesskey*') ? 'active':'' }}"><a href="{{ route('accesskey.index') }}">Account Linking</a></li>
					<li class="{{ (Request::is('profile*') || Request::is('apikey*')) ? 'active':'' }}"><a class="dropdown-trigger" href="#" data-target="user-dropdown">Hi {{ Auth::user()->name }}!<i class="material-icons right">arrow_drop_down</i></a></li>
				</ul>
			</div>
			
			{{-- mobile menu --}}
			<ul class="sidenav" id="mobile-demo">
				<li class="{{ Request::is('device*') ? 'active':'' }}"><a href="{{ route('device.index') }}">Devices</a></li>
				<li class="{{ Request::is('accesskey*') ? 'active':'' }}"><a href="{{ route('accesskey.index') }}">Account Linking</a></li>
				<li class="{{ Request::is('profile*') ? 'active':'' }}"><a href="{{ route('profile.index') }}">My Account</a></li>
				<li class="{{ Request::is('apikey*') ? 'active':'' }}"><a href="{{ route('apikey.index') }}">API Keys</a></li>
				<li><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a></li>
			</ul>
		</nav>
	</div>

    @yield('loggedin_content')

@endsection