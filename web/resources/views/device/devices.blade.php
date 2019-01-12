@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		@if((Auth::user()->device_limit <= env('KSERVICES_HOSTED_FREELIMIT')) && env('KSERVICES_HOSTED', false))
		<div class="row">
			<div class="col s12">
				<div class="card-panel green white-text">
					Since you are using the free gBridge plan, you may only create up to {{env('KSERVICES_HOSTED_FREELIMIT')}} devices.<br>
					You are currently using {{Auth::user()->devices()->count()}} devices.<br>
					<a class="grey-text text-lighten-4" href="https://gbridge.kappelt.net/subscription/overview" target="_blank">Need more devices and want to support our work? Consider upgrading to gBridge standard. You only need to pay what you want! Click here to show subscription options.</a>
				</div>
			</div>
		</div>
		@endif

		@if(Auth::user()->device_limit < Auth::user()->devices()->count())
		<div class="row">
			<div class="col s12">
				<div class="card-panel red white-text">
					<b>You have currently created {{Auth::user()->devices()->count()}} devices, while your account is limited to {{Auth::user()->device_limit}} devices.<br>
					Google commands will fail until you remove at least {{Auth::user()->devices()->count() - Auth::user()->device_limit}} of them.</b>
				</div>
			</div>
		</div>
		@endif

		<div class="right">
			<a class="waves-effect btn blue" href="{{ route('device.create') }}">
				<i class="material-icons left">add</i>Device
			</a>
		</div>

		

		<h2>All Devices</h2>
		
		@if (count($devices) > 0)

			@foreach($devices as $device)
				<div class="row">
					<div class="col s12">
						<div class="card-panel hoverable">
							<div class="row">
								<span style="font-size: 1.64rem">{{ $device->deviceType->name }}: <b>{{ $device->name }}</b></span>

								<div class="right">
									{!!Form::open(['action' => ['DeviceController@destroy', $device->device_id], 'method' => 'POST'])!!}
										<a class="waves-effect btn blue" href="{{ route('device.edit', $device->device_id) }}"><i class="material-icons">edit</i></a>
										{{Form::hidden('_method', 'DELETE')}}
										{{Form::button('<i class="material-icons">delete</i>', ['type' => 'submit', 'class' => 'waves-effect btn blue'], false)}}
									{!!Form::close()!!}
								</div>
							</div>

							@if($device->twofa_type)
							<div class="row">
								<div class="col s12">
									<p>
										@if($device->twofa_type == 'ack')
										Google security confirmation is enabled.
										@elseif($device->twofa_type == 'pin')
										Google pin code security is enabled.
										@endif
									<p>
								</div>
							</div>
							@endif

							<div class="row">
								<div class="col s12">
									<b>Features and MQTT-Topics:</b>
								</div>
								@foreach($device->traits as $trait)
								<div class="col s12 m12 l2 valign-wrapper">
									<div>{{ $trait->name }}</div>
								</div>
								<div class="col s12 m6 l5">
									@if($trait->needsActionTopic)
									<input type="text" readonly value="{{ 'gBridge/u' . Auth::user()->user_id . '/' . $trait->pivot->mqttActionTopic }}">
									@endif
								</div>
								<div class="col s12 m6 l5">
									@if($trait->needsStatusTopic)
									<input type="text" readonly value="{{ 'gBridge/u' . Auth::user()->user_id . '/' . $trait->pivot->mqttStatusTopic }}">
									@endif
								</div>
								@endforeach
							</div>
							
						</div>
					</div>
				</div>
			@endforeach
		@else
			{{-- See https://github.com/Dogfalo/materialize/issues/2340 for dialogs --}}
			<div class="card-panel green white-text">
				No devices created yet.<br>
				Let's add one by pressing the Button above!<br>
				Need help? Visit <a class="grey-text text-lighten-2" href="https://doc.gbridge.kappelt.net/html/firstSteps/gettingStarted.html" target="_blank">https://doc.gbridge.kappelt.net</a> for documentation and a quickstart guide.
			</div>
		@endif
	</div>

@endsection