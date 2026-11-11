@extends('layouts.app')
@section('title','Users')
@section('content')
<h1>Users</h1><p class="sub">View, edit, or suspend guest and host accounts.</p>
<div class="tbl" style="--c:1fr .7fr 1.2fr .8fr 1.4fr">
<div class="tr th"><div>Name</div><div>Role</div><div>Email</div><div>Status</div><div>Actions</div></div>
@foreach($users as $u)
<div class="tr"><div class="cell">{{ $u->name }}</div><div class="cell" data-l="Role">{{ ucfirst($u->role) }}</div><div class="cell" data-l="Email">{{ $u->email }}</div>
<div class="cell"><span class="bd {{ $u->status==='active'?'ok':'bad' }}">{{ ucfirst($u->status) }}</span></div>
<div class="cell act">@if($u->role==='admin') - @else
<a class="btn" href="{{ route('admin.users.edit',$u) }}">Edit</a>
<form method="post" action="{{ route('admin.users.status',$u) }}" @if($u->status==='active') onsubmit="return confirm('Suspend {{ addslashes($u->name) }}? They will not be able to log in until reactivated.')" @endif>@csrf
<input type="hidden" name="status" value="{{ $u->status==='active'?'suspended':'active' }}"><button class="btn">{{ $u->status==='active'?'Suspend':'Reactivate' }}</button></form>
@endif</div></div>
@endforeach
</div>
@endsection
