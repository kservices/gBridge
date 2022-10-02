@if (count($errors) > 0)
	<div class="row">
		<form class="col s12">
			<div class="card red">
				<div class="card-content white-text">
					<span class="card-title">An error occured</span>
					<ul>
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			</div>
		</form>
	</div>
@endif

@if (session('success'))
	<div class="row">
		<form class="col s12">
			<div class="card green">
				<div class="card-content black-text">
					<span class="card-title">Success</span>
					{{ session('success') }}
				</div>
			</div>
		</form>
	</div>
@endif

@if (session('error'))
	<div class="row">
		<form class="col s12">
			<div class="card red">
				<div class="card-content white-text">
					<span class="card-title">An error occured</span>
					{{ session('error') }}
				</div>
			</div>
		</form>
	</div>
@endif