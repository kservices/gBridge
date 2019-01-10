@extends('layout.loggedin') @section('loggedin_content')

<div class="container">
	@include('common.messages')

	<div class="right">
		<a href="{{ route('device.index') }}" class="btn waves-effect blue">
			<i class="material-icons left">arrow_back</i>Overview
		</a>
	</div>

	<div class="row">
		<h2>Edit Device</h2>
		<form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('device.update', ['id' => $device->device_id]) }}">
			{{ csrf_field() }}
			<div class="row">
				<div class="input-field col s12">
					<input type="text" class="validate" id="name" name="name" value="{{ $device->name }}" required autofocus>
					<label for="name">Name</label>
				</div>
			</div>
			<div class="row">
				<div class="input-field col s12 m6">
					<select class="validate" name="type" required>
						@foreach($devicetypes as $devicetype)
						<option value="{{ $devicetype->devicetype_id }}" {{ ($devicetype->devicetype_id === $device->devicetype_id) ? ' selected':'' }}>{{ $devicetype->name }}</option>
						@endforeach
					</select>
					<label for="type">Device Type</label>
				</div>
				<div class="input-field col s12 m6">
					<?php
                        //prepare supported trait list
                        $traitIds = $device->traits->pluck('traittype_id')->toArray();
                    ?>
					<select class="validate" name="traits[]" required multiple>
						@foreach($traittypes as $traittype)
						<option value="{{ $traittype->traittype_id }}" {{ in_array($traittype->traittype_id, $traitIds) ? ' selected':'' }}>{{ $traittype->name }}</option>
						@endforeach
					</select>
					<label for="traits[]">Traits</label>
				</div>
			</div>
			<input name="_method" type="hidden" value="PUT">
			<button class="btn waves-effect blue">
				<i class="material-icons left">save</i>Save Device</button>
		</form>
	</div>

	<div class="row">
		<h2>Edit Topics and Settings</h2>

		@foreach($device->traits as $trait)
		<form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('device.updatetopic', ['device' => $device, 'traittype' => $trait]) }}">
			{{ csrf_field() }} 
			<div class="row">
				<div class="col s12">
					Trait:
					<b>{{$trait->name}}</b>
				</div>

				{{-- Some traits have special settings --}}

				{{-- Special settings for TempSet.Humidity --}}
				@if($trait->shortname == 'TempSet.Humidity')
				<div class="col s11 offset-s1">
					<br>
					<label class="black-text">
						<input type="checkbox" id="humiditySupported" name="humiditySupported" @if($trait->pivot->config->get('humiditySupported'))checked="checked"@endif>
						<span>Device is able to report humidity</span>
					</label>
				</div>
				@endif

				{{-- Special settings for TempSet.Mode --}}
				@if($trait->shortname == 'TempSet.Mode')

				@php
				$supportedThermostatModes = [];
				if(!is_null($trait->pivot->config->get('modesSupported'))){
					$supportedThermostatModes = $trait->pivot->config->get('modesSupported');
				}
				@endphp
				
				<div class="input-field col s11 offset-s1">
					<br>
					<select id="modes[]" name="modes[]" required multiple>
						<option value="off" {{ in_array('off', $supportedThermostatModes) ? 'selected':''}}>Off</option>
						<option value="heat" {{ in_array('heat', $supportedThermostatModes) ? 'selected':''}}>Heating</option>
						<option value="cool" {{ in_array('cool', $supportedThermostatModes) ? 'selected':''}}>Cooling</option>
						<option value="on" {{ in_array('on', $supportedThermostatModes) ? 'selected':''}}>On</option>
						<option value="auto" {{ in_array('auto', $supportedThermostatModes) ? 'selected':''}}>Automatic</option>
						<option value="fan-only" {{ in_array('fan-only', $supportedThermostatModes) ? 'selected':''}}>Fan-only</option>
						<option value="purifier" {{ in_array('purifier', $supportedThermostatModes) ? 'selected':''}}>Purifying</option>
						<option value="eco" {{ in_array('eco', $supportedThermostatModes) ? 'selected':''}}>Energy Saving</option>
						<option value="dry" {{ in_array('dry', $supportedThermostatModes) ? 'selected':''}}>Dry Mode</option>
					</select>
					<label for="modes[]">Supported Thermostat Modes</label>
				</div>
				@endif

				@if($trait->needsActionTopic)
				<div class="col s11 offset-s1">
					<b>Action Topic: </b> gBridge/u{{Auth::user()->user_id}}/
					<div class="input-field inline">
						<input type="text" class="validate" id="{{$trait->traittype_id . '-action'}}" name="action" size="100" value="{{ $trait->pivot->mqttActionTopic }}" required>
						<label for="{{$trait->traittype_id . '-action'}}">Action Topic</label>
					</div>
				</div>
				@else
				<input type="hidden" id="{{$trait->traittype_id . '-action'}}" name="action" value="{{ $trait->pivot->mqttActionTopic }}">
				@endif

				@if($trait->needsStatusTopic)
				<div class="col s11 offset-s1">
					<b>Status Topic: </b> gBridge/u{{Auth::user()->user_id}}/
					<div class="input-field inline">
						<input type="text" class="validate" id="{{$trait->traittype_id . '-status'}}" name="status" size="100" value="{{ $trait->pivot->mqttStatusTopic }}" required>
						<label for="{{$trait->traittype_id . '-status'}}">Status Topic</label>
					</div>
				</div>
				@else
				<input type="hidden" id="{{$trait->traittype_id . '-status'}}" name="status" value="{{ $trait->pivot->mqttStatusTopic }}">
				@endif

				<input name="_method" type="hidden" value="PUT">
				<button class="btn waves-effect blue">
				<i class="material-icons left">save</i>Save Settings</button>
			</div>
		</form>
		<br>
		@endforeach
	</div>
</div>
@endsection