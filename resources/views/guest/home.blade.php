@extends('layouts.app')
@section('title','Stays')
@section('content')
<h1>Find a stay</h1><p class="sub">Request dates. Check-in is 2:00 PM and check-out is 11:00 AM.</p>
<div class="cards stays" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
@forelse($listings as $l)
<div class="card stay"><b style="font-size:18px;line-height:24px">{{ $l->name }}</b><small>${{ $l->price }} / night - {{ $l->availabilityLabel() }}</small>
<form method="post" action="{{ route('guest.bookings.store') }}">@csrf<input type="hidden" name="listing_id" value="{{ $l->id }}">
<div><label>Check-in<input type="date" name="check_in" min="{{ now()->toDateString() }}" required></label></div>
<div><label>Check-out<input type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}" required></label></div>
<button class="btn p">Request booking</button></form></div>
@empty<div class="empty">No listings are open right now. Check back soon.</div>@endforelse
</div>
<h2>My bookings</h2>
<div class="tbl" style="--c:1.3fr 1fr .8fr 1fr">
<div class="tr th"><div>Property</div><div>Dates</div><div>Status</div><div>Door code</div></div>
@forelse($bookings as $b)
<div class="tr"><div class="cell">{{ $b->listing->name }}</div><div class="cell" data-l="Dates">{{ $b->range }}</div>
<div class="cell"><span class="bd {{ $b->status==='approved'?'ok':(in_array($b->status,['declined','cancelled'])?'bad':'w') }}">{{ ucfirst($b->status) }}</span></div>
<div class="cell" data-l="Door code">{{ $b->status==='approved' && $b->access_status==='active' ? $b->access_code : '-' }}</div></div>
@empty<div class="empty">No bookings yet. Request dates above to get started.</div>@endforelse
</div>
@endsection
