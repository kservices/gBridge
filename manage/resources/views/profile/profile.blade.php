@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
		@include('common.messages')

		<h3>Hi, {{ $user->email }}!</h3>
		
		<div class="row">
			<div class="col s12 m6">
				<div class="card blue-grey lighten-4 black-text">
					<form class="form-horizontal" method="POST" accept-charset="UTF-8" action="{{ route('profile.updatepwd') }}">
						{{ csrf_field() }}
						<div class="card-content black-text">
							<p><span class="card-title">Change Password</span></p>
							<div class="input-field">
								<input type="password" class="validate" id="password" name="password" required>
								<label for="password">Current Password</label>
							</div>
							<div class="input-field">
								<input type="password" class="validate" id="newpassword" name="newpassword" required>
								<label for="newpassword">New Password</label>
							</div>
							<div class="input-field">
								<input type="password" class="validate" id="newpassword_confirmation" name="newpassword_confirmation" required>
								<label for="newpassword_confirmation">New Password (confirm)</label>
							</div>
						</div>
						<div class="card-action">
							<button style="width: 100%;" class="btn waves-effect blue" type="submit">
								<i class="material-icons left">save</i>Change Password
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

@endsection