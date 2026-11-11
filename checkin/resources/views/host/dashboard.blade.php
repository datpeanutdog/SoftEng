@extends('layouts.app')
@section('title','Booking requests')
@section('content')
<h1>Booking requests</h1><p class="sub">Review new requests for your listings.</p>
<div class="cards">
<div class="card"><small>Pending requests</small><b>{{ $pending->count() }}</b></div>
<div class="card"><small>Guests checked in</small><b>{{ $checkedIn }}</b></div>
<div class="card"><small>Low battery locks</small><b>{{ $low }}</b></div>
</div>
<h2>Incoming requests</h2>
<div class="tbl" style="--c:1fr 1.2fr 1fr .8fr 1.3fr">
<div class="tr th"><div>Guest</div><div>Property</div><div>Dates</div><div>Status</div><div>Action</div></div>
@forelse($pending as $b)
<div class="tr"><div class="cell">{{ $b->guest->name }}</div><div class="cell" data-l="Property">{{ $b->listing->name }}</div><div class="cell" data-l="Dates">{{ $b->range }}</div>
<div class="cell"><span class="bd w">Pending</span></div>
<div class="cell act">@include('partials.actions',['b'=>$b,'act'=>'decline'])</div></div>
@empty<div class="empty">No pending requests. New requests will show up here.</div>@endforelse
</div>
@endsection
