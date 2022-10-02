@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<h2>Google Account Linking</h2>
		
		@if(\Carbon\Carbon::create(2018, 11, 24, 15, 0, 0)->gt(Auth::user()->created_at))
		<div class="card-panel green white-text">
			<i class="material-icons center">info</i><br>
			From now on, you don't need to generate accesskeys anymore. Just enter your account's password when linking in the Google Home app.
		</div>
		@endif

		<div class="card-panel blue white-text">
			<i class="material-icons center">info</i><br>
			This page shows all linkings between gBridge and your Google Home system.<br>
			You can delete a linking here, the belonging Google account will be unable to communicate with gBridge. Un-link and relink your gBridge-Account in the Google Home App then.
		</div>

		@if (count($accesskeys) > 0)
			<table class="highlight responsive">
				<!-- Table Headings -->
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
					<tr>
				</thead>

				<!-- Table Body -->
				<tbody>
					@foreach ($accesskeys as $accesskey)
						<tr>
							<td>
								Linking with Google on {{ $accesskey->generated_at }}
							</td>
							<td>
								{!!Form::open(['action' => ['App\Http\Controllers\AccesskeyController@destroy', $accesskey->accesskey_id], 'method' => 'POST'])!!}
									{{Form::hidden('_method', 'DELETE')}}
									{{Form::button('<i class="material-icons">delete</i>', ['type' => 'submit', 'class' => 'waves-effect btn blue'], false)}}
								{!!Form::close()!!}
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			{{-- See https://github.com/Dogfalo/materialize/issues/2340 for dialogs --}}
			<div class="card-panel green white-text">
				You haven't linked gBridge with Google yet.<br>
				Use the Google Home app, choose to add new smart home and search for "Kappelt gBridge"
			</div>
		@endif
	</div>

@endsection