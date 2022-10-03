@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<h2>Google Account Linking</h2>
		

		<div class="card-panel blue white-text">
			<i class="material-icons center">info</i><br>
			This page shows all linking between Bridge and your Google Home system.<br>
			You can delete a linking here, the belonging Google account will be unable to communicate with Bridge. Un-link and relink your Bridge-Account in the Google Home App then.
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
				You haven't linked Bridge with Google yet.<br>
				Use the Google Home app, choose to add new smart home and search for "Bridge"
			</div>
		@endif
	</div>

@endsection