@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<div class="right">
			<a href="{{ route('device.index') }}" class="btn waves-effect blue">
				<i class="material-icons left">arrow_back</i>Overview
			</a>
		</div>

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
						<option value="{{ $devicetype->devicetype_id }}"{{ ($devicetype->devicetype_id === $device->devicetype_id) ? ' selected':'' }}>{{ $devicetype->name }}</option>
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
						<option value="{{ $traittype->traittype_id }}"{{ in_array($traittype->traittype_id, $traitIds) ? ' selected':'' }}>{{ $traittype->name }}</option>
						@endforeach
					</select>
					<label for="traits[]">Traits</label>
				</div>
            </div>
            <input name="_method" type="hidden" value="PUT">
			<button class="btn waves-effect blue"><i class="material-icons left">save</i>Save</button>
        </form>
	</div>
@endsection