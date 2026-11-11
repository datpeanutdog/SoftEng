@extends('layouts.app')
@section('title','Listings')
@section('content')
<div class="hd"><div><h1>Listings</h1><p class="sub">Add, edit, or remove your properties.</p></div>
<a class="btn p" href="{{ route('host.listings.create') }}">Add listing</a></div>
<div class="tbl" style="--c:1.4fr .8fr 1fr .8fr 1.3fr">
<div class="tr th"><div>Listing</div><div>Price / night</div><div>Availability</div><div>Status</div><div>Actions</div></div>
@forelse($listings as $l)
<div class="tr"><div class="cell">{{ $l->name }}</div><div class="cell" data-l="Price / night">${{ $l->price }}</div><div class="cell" data-l="Availability">{{ $l->availabilityLabel() }}</div>
<div class="cell"><span class="bd {{ $l->status==='active'?'ok':'' }}">{{ ucfirst($l->status) }}</span></div>
<div class="cell act"><a class="btn" href="{{ route('host.listings.edit',$l) }}">Edit</a>
<form method="post" action="{{ route('host.listings.destroy',$l) }}" onsubmit="return confirm('Delete {{ addslashes($l->name) }}? This cannot be undone.')">@csrf @method('DELETE')<button class="btn">Delete</button></form></div></div>
@empty<div class="empty">No listings yet. Add your first property to start receiving requests.</div>@endforelse
</div>
@endsection
