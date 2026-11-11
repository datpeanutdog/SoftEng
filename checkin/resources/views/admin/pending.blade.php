@extends('layouts.app')
@section('title','Pending bookings')
@section('content')
<h1>Pending bookings</h1><p class="sub">Approve or cancel bookings waiting for review.</p>
<div class="cards">
<div class="card"><small>Pending</small><b>{{ $pending->count() }}</b></div>
<div class="card"><small>Approved today</small><b>{{ $approved }}</b></div>
<div class="card"><small>Cancelled today</small><b>{{ $cancelled }}</b></div>
</div>
<h2>Waiting for review</h2>
<div class="tbl" style="--c:.8fr 1fr 1.2fr 1fr 1.3fr">
<div class="tr th"><div>Booking</div><div>Guest</div><div>Property</div><div>Dates</div><div>Action</div></div>
@forelse($pending as $b)
<div class="tr"><div class="cell">#{{ $b->id }}</div><div class="cell" data-l="Guest">{{ $b->guest->name }}</div><div class="cell" data-l="Property">{{ $b->listing->name }}</div><div class="cell" data-l="Dates">{{ $b->range }}</div>
<div class="cell act">@include('partials.actions',['b'=>$b,'act'=>'cancel'])</div></div>
@empty<div class="empty">Nothing waiting for review.</div>@endforelse
</div>
@endsection
