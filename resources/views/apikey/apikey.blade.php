@extends('layout.loggedin')

@section('loggedin_content')

	<div class="container">
        @include('common.messages')
        
        @if(session('currentApiKey'))
        <div class="card-panel green">
            <div class="col s12">
                <h4>Your new API Key:</h4>
                <input type="text" id="newApiKey" onClick="this.setSelectionRange(0, this.value.length)" readonly value="{{ session('currentApiKey') }}">
            </div>
        </div>
        @endif

		<div class="right">
			<a class="waves-effect btn blue" href="{{ route('apikey.createStandard') }}">
				<i class="material-icons left">add</i>New API Key (Standard)
            </a>
            <a class="waves-effect btn blue" href="{{ route('apikey.createUser') }}">
				<i class="material-icons left">add</i>New API Key (Elevated)
			</a>
		</div>

		

		<h2>API Keys</h2>
		
		@if (count($apikeys) > 0)

			@foreach($apikeys as $number => $apikey)
				<div class="row">
					<div class="col s12">
						<div class="card-panel hoverable">
							<div class="row">
								<span style="font-size: 1.64rem">API Key (Created at {{ $apikey->created_at }})</b></span>

								<div class="right">
									{!!Form::open(['action' => ['App\Http\Controllers\ApiKeyController@destroy', $apikey->apikey_id], 'method' => 'POST'])!!}
										{{Form::hidden('_method', 'DELETE')}}
										{{Form::button('<i class="material-icons">delete</i>', ['type' => 'submit', 'class' => 'waves-effect btn blue'], false)}}
									{!!Form::close()!!}
								</div>
                            </div>

                            <div class="row">
								<div class="col s12">
									<p><b>Key Identifier: </b>{{ $apikey->identifier }}</p>
								</div>
                                <div class="col s12">
                                    <p><b>Key Privileges: </b></p>
                                    <ul>
                                        <li>Standard (Manage devices)</li>
                                        @if($apikey->privilege_user)<li>User (Get account information, change passwords)</li>@endif
                                    </ul>
                                </div>
                            </div>							
						</div>
					</div>
				</div>
			@endforeach
		@else
			<div class="card-panel green white-text">
				You haven't created any keys for Bridge's API yet.<br>
				Let's create one by pressing one of the buttons above!<br>
			</div>
		@endif
	</div>

@endsection