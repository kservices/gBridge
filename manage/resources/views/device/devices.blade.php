@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<div class="right">
			<a class="waves-effect btn blue" href="{{ route('device.create') }}">
				<i class="material-icons left">add</i>Device
			</a>
		</div>

		<h2>All Devices</h2>
		
		@if (count($devices) > 0)
			<table class="striped highlight responsive">
				<!-- Table Headings -->
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
					</tr>
				</thead>

				<!-- Table Body -->
				<tbody>
					@foreach ($devices as $device)
						<tr>
							<td>
								<b>{{ $device->deviceType->name }}:</b> {{ $device->name }}<br>
								<ul>
									<li style="list-style-type: none;"><b>Features:</b></li>
									@foreach ($device->traits as $trait)
									<li>{{ $trait->name }}</li>
									@endforeach
								</ul>
							</td>
							<td>
								{!!Form::open(['action' => ['DeviceController@destroy', $device->device_id], 'method' => 'POST'])!!}
									<a class="waves-effect btn blue" href="{{ route('device.edit', $device->device_id) }}"><i class="material-icons">edit</i></a>
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
			<div class="card-panel blue white-text">
				No devices created yet.<br>
				Let's add one by pressing the Button above!
			</div>
		@endif
	</div>

@endsection