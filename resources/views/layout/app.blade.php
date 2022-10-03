<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Pecwill Bridge: Control anything with Google Assistant">
	<meta name="author" content="Kappelt kServices">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" type="image/png" href="{{ asset('img/kappelt-196.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/kappelt-180.png') }}">

	<title>@if(!empty($site_title)) {{ $site_title . ' | '}}@endif Pecwill Bridge</title>

	<link rel="stylesheet" charset="utf-8" href="https://fonts.googleapis.com/icon?family=Material+Icons"> 
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-rc.2/css/materialize.min.css">

	<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-rc.2/js/materialize.min.js"></script>

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
		<div class="blue darken-1 footer-copyright">
			<div class="container">
				<a class="grey-text text-lighten-4" href="https://ochui.dev">©{{ date('Y') }} Ochui Princewill</a>
			</div>
		</div>
	</footer>

	@yield('customScripts')
</body>

</html>