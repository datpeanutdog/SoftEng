@extends('layouts.plain')
@section('title','Create account')
@section('content')
<h1>Create a guest account</h1>
@if($errors->any())<div class="flash bad" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('register') }}">@csrf
<label>Name<input name="name" value="{{ old('name') }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Password (8+ characters)<input type="password" name="password" required></label>
<label>Confirm password<input type="password" name="password_confirmation" required></label>
<button class="btn p" style="width:100%">Create account</button>
</form>
<p class="note">Already registered? <a href="{{ route('login') }}">Log in</a></p>
@endsection
