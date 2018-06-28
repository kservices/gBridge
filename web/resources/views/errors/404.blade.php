@extends('layout.app') 

@section('app_content') 

<div class="container">
    <div class="row">
        &nbsp;
    </div>

    <div class="row">
        <div class="col m12 l8 offset-l2">
            <div class="card blue-grey lighten-4 black-text">
                <div class="card-content black-text">
                    <p><span class="card-title">404: Page not found</span></p>
                    <h2><i style="font-size: 1em;" class="material-icons">search</i> As it seems, this page doesn't exist...</h2>
                    {{ $exception->getMessage() }}<br><br>
                    We're sorry for any inconvenience caused.<br>
                    Do you just want to go back to the <a href="{{ url('/') }}">home page</a>?.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection