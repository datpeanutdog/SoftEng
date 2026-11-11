@extends('layouts.app')
@section('title','Edit user')
@section('content')
<h1>Edit user</h1><p class="sub">Update this account's details.</p>
<form class="fm" method="post" action="{{ route('admin.users.update',$user) }}">@csrf @method('PUT')
<label>Name<input name="name" value="{{ old('name',$user->name) }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email',$user->email) }}" required></label>
<label>Role<select name="role"><option value="guest" @selected(old('role',$user->role)==='guest')>Guest</option><option value="host" @selected(old('role',$user->role)==='host')>Host</option></select></label>
<div class="row"><a class="btn" href="{{ route('admin.users') }}">Cancel</a><button class="btn p">Save changes</button></div>
</form>
@endsection
