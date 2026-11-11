@extends('layouts.app')
@section('title','Flagged bookings')
@section('content')
<h1>Flagged bookings</h1><p class="sub">These bookings need manual approval from an admin. The rules below flag them automatically.</p>
<div class="cards two" style="grid-template-columns:1fr 1fr">
<div class="card"><small>First booking on a new account</small><b>{{ $first }} flagged</b></div>
<div class="card"><small>Booked within 24 hours of check-in</small><b>{{ $late }} flagged</b></div>
</div>
<h2>Waiting for review</h2>
<div class="tbl" style="--c:.8fr 1fr 1.2fr 1fr 1.3fr">
<div class="tr th"><div>Booking</div><div>Guest</div><div>Reason flagged</div><div>Check-in</div><div>Action</div></div>
@forelse($bookings as $b)
<div class="tr"><div class="cell">#{{ $b->id }}</div><div class="cell" data-l="Guest">{{ $b->guest->name }}</div>
<div class="cell"><span class="bd w">{{ \App\Models\Booking::FLAGS[$b->flag_reason] ?? 'Flagged' }}</span></div>
<div class="cell" data-l="Check-in">{{ $b->check_in->format('M d') }}</div>
<div class="cell act">@include('partials.actions',['b'=>$b,'act'=>'cancel'])</div></div>
@empty<div class="empty">No flagged bookings. Everything else is waiting on hosts.</div>@endforelse
</div>
@endsection
