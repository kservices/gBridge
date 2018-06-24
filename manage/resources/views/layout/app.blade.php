<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Kappelt kBridge: Control anything with Google Assistant">
	<meta name="author" content="Kappelt kServices">
	<!--<link rel="icon" href="">-->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@if(!empty($site_title)) {{ $site_title . ' | '}}@endif Kappelt gBridge</title>

	<link rel="stylesheet" charset="utf-8" href="https://fonts.googleapis.com/icon?family=Material+Icons"> {!! MaterializeCSS::include_secure_css() !!}
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script> {!! MaterializeCSS::include_secure_js() !!}

	<script>
		$(document).ready(function () {
			//For mobile navbar
			$('.sidenav').sidenav();
			$('select').formSelect();
			$(".dropdown-trigger").dropdown();
			$('.tooltipped').tooltip();
		});
	</script>
	<style>
		body {
			display: flex;
			min-height: 100vh;
			flex-direction: column;
		}
		main {
			flex: 1 0 auto;
		}
	</style>

	@yield('customHead')
</head>

<body>
	<main>
		@yield('app_content')
	</main>

	<footer class="blue page-footer">
		<div class="container">
			<div class="row">
				<div class="col m2 s6" style="height: 100%">
					<img src="{{ asset('img/kappelt-logo.png') }}" alt="Kappelt Logo" class="responsive-img" style="max-width: 100%;">
				</div>
				<div class="col m6 s6">
					<h5 class="white-text">Kappelt gBridge</h5>
				</div>
				<div class="col m4 s12">
					<h5 class="white-text">Links</h5>
					<ul>
						<li>
							<a class="grey-text text-lighten-3" href="https://about.gbridge.kappelt.net/privacy">Privacy Policy</a>
						</li>
						<li>
							<a class="grey-text text-lighten-3" href="https://about.gbridge.kappelt.net/imprint">Imprint</a>
						</li>
						<li>
							<a class="grey-text text-lighten-3" href="https://github.com/kservices/gBridge">gBridge on Github</a>
						</li>
					</ul>
				</div>
				@if(!env('KSERVICES_HOSTED', false))
				<div class="col s12">
					<a class="white-text" href="https://about.gbridge.kappelt.net"><b>Want to support my work and save time?</b> Consider using gBridge as an inexpensive service hosted by me.</a>
				</div>
				@endif
			</div>
		</div>
		<div class="blue darken-1 footer-copyright">
			<div class="container">
				©{{ date('Y') }} Kappelt kServices
				<a class="grey-text text-lighten-4 right" href="https://about.gbridge.kappelt.net">https://about.gbridge.kappelt.net</a>
			</div>
		</div>
	</footer>

	@yield('customScripts')
</body>

</html>