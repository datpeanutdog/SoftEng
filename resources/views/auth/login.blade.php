@extends('layouts.plain')
@section('title','Log in')
@section('content')
<h1>Log in</h1>
@if($errors->any())<div class="flash bad" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('login') }}">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
<label>Password<input type="password" name="password" required></label>
<button class="btn p" style="width:100%">Log in</button>
</form>
<p class="note">New guest? <a href="{{ route('register') }}">Create an account</a></p>
<p class="note">Demo accounts (password: <b>password</b>): admin@email.com, lea@email.com (host), maria@email.com (guest)</p>
@endsection
