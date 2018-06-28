@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<div class="right">
			<a class="waves-effect btn blue" href="{{ route('accesskey.create') }}">
				<i class="material-icons left">add</i>Accesskey
			</a>
		</div>

		<h2>Accesskeys</h2>
		
		<div class="card-panel blue white-text">
			<i class="material-icons center">info</i><br>
			Accesskeys are temporary passwords, that can be used to log in to gBridge with Google Assistant.
			Once you select "Kappelt gBridge" in the Google Home App (or alike), you'll be prompted to enter your email and an accesskey.
			Note the following:
			<ul>
				<li>An Accesskey can only be used once.</li>
				<li>An Accesskey has to be used during one hour after its generation. It'll become invalid afterwards.</li>
				<li>If you delete an Accesskey, the linked Google account will be unable to communicate with gBridge. Un-link and relink your gBridge-Account in the Google Home App then.</li>
			</ul> 
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
								@if($accesskey->status === 'USED')
								<p class="green-text">
								@elseif($accesskey->status === 'EXPIRED')
								<p class="red-text">
								@elseif($accesskey->status === 'READY')
								<p class="blue-text">
								@else
								<p>
								@endif
								<b title="Identifier u{{Auth::user()->user_id}}a{{$accesskey->accesskey_id}}">Accesskey No. {{ $loop->iteration }}</b><br>
								Valid until {{ $accesskey->valid_until }} CE(S)T.
								</p>

								@if($accesskey->status === 'USED')
								Accesskey: <input type="text" value="&lt;hidden&gt;" readonly><br>
								<b>This key has been used to register an Google Home account at {{ $accesskey->used_at }} CE(S)T</b>
								@elseif($accesskey->status === 'EXPIRED')
								Accesskey: <input style="font-family: monospace;" type="text" value="{{ $accesskey->password }}" readonly>
								<b>This accesskey has expired. You may delete this key and create a new one.</b>
								@elseif($accesskey->status === 'READY')
								Accesskey: <input style="font-family: monospace;" type="text" value="{{ $accesskey->password }}" readonly>
								<b>This key is ready to be used.</b>
								@else
								The status of this accesskey is unknown.
								@endif
							</td>
							<td>
								{!!Form::open(['action' => ['AccesskeyController@destroy', $accesskey->accesskey_id], 'method' => 'POST'])!!}
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
				No accesskey created yet.<br>
				Create one by pressing the button above!
			</div>
		@endif
	</div>

@endsection