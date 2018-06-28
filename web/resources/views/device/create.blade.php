@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<div class="right">
			<a href="{{ route('device.index') }}" class="btn waves-effect blue">
				<i class="material-icons left">arrow_back</i>Overview
			</a>
		</div>

		<h2>New Device</h2>

		<form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('device.store') }}">
			{{ csrf_field() }}
			<div class="row">
				<div class="input-field col s12">
					<input type="text" class="validate" id="name" name="name" required autofocus>
					<label for="name">Name</label>
				</div>
			</div>
			<div class="row">
				<div class="input-field col s12 m6">
					<select class="validate" name="type" required>
						<option value="" disabled selected>Select Device Type</option>
						@foreach($devicetypes as $devicetype)
						<option value="{{ $devicetype->devicetype_id }}">{{ $devicetype->name }}</option>
						@endforeach
					</select>
					<label for="type">Device Type</label>
				</div>
				<div class="input-field col s12 m6">
					<select class="validate" name="traits[]" required multiple>
						<option value="" disabled selected>Select Supported Traits</option>
						@foreach($traittypes as $traittype)
						<option value="{{ $traittype->traittype_id }}">{{ $traittype->name }}</option>
						@endforeach
					</select>
					<label for="traits[]">Traits</label>
				</div>
			</div>
			<button class="btn waves-effect blue"><i class="material-icons left">add</i>Add</button>
		</form>
	</div>
@endsection