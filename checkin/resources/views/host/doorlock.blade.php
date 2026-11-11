@extends('layouts.app')
@section('title','Doorlock')
@section('content')
@if(!$listing)
<h1>Doorlock</h1><p class="sub">Battery, access, and activity for your locks.</p>
<div class="empty" style="border:1px solid var(--line);border-radius:12px">Add a listing first. Each listing has its own smart lock.</div>
@else
<h1>Doorlock - {{ $listing->name }}</h1><p class="sub">Battery, access, and activity for this lock.</p>
@if($listings->count()>1)
<form method="get" class="demo" style="margin:-8px 0 20px;border:0;padding:0"><label for="ls">Listing</label>
<select id="ls" name="listing" onchange="this.form.submit()">@foreach($listings as $l)<option value="{{ $l->id }}" @selected($l->id===$listing->id)>{{ $l->name }}</option>@endforeach</select></form>
@endif
@if($listing->battery<20)
<div class="alert"><span>Battery is below 20%. Replace it soon so guests are not locked out.</span>
<form method="post" action="{{ route('host.battery.replaced',$listing) }}">@csrf<button class="btn p">Mark battery replaced</button></form></div>
@endif
@php $last = $events->first(fn($e)=>str_starts_with($e->event,'Door unlocked')); @endphp
<div class="cards lock">
<div class="card"><small>Battery</small><div class="batt {{ $listing->battery<20?'low':'' }}"><i><u style="width:{{ $listing->battery }}%"></u></i>{{ $listing->battery }}%</div></div>
<div class="card"><small>Lock status</small><b>Locked</b><small style="margin-top:6px">Last access {{ $last ? $last->created_at->format('M d, g:i A') : 'none yet' }}</small></div>
<div class="card"><small>Check-in / check-out</small><b>2:00 PM / 11:00 AM</b><small style="margin-top:6px">Applies to all new bookings</small></div>
</div>
<h2>Guest access</h2>
<div class="tbl" style="--c:1fr 1fr 1fr 1fr">
<div class="tr th"><div>Guest</div><div>Booking</div><div>Code status</div><div>Action</div></div>
@forelse($access as $a)
<div class="tr"><div class="cell">{{ $a->guest->name }}</div><div class="cell" data-l="Booking">{{ $a->range }}</div>
<div class="cell"><span class="bd {{ $a->access_status==='active'?'ok':'' }}">{{ $a->access_status==='active'?'Active':'Removed' }}</span></div>
<div class="cell act">@if($a->access_status==='active')<form method="post" action="{{ route('host.access.remove',$a) }}" onsubmit="return confirm('Remove access for {{ addslashes($a->guest->name) }}?')">@csrf<button class="btn">Remove access</button></form>@else - @endif</div></div>
@empty<div class="empty">No guests have access yet. Approved bookings get a code automatically.</div>@endforelse
</div>
<h2>Lock event log</h2>
<div class="tbl" style="--c:1fr 1fr 1fr">
<div class="tr th"><div>Time</div><div>Event</div><div>Result</div></div>
@forelse($events as $e)
<div class="tr"><div class="cell">{{ $e->created_at->format('M d, g:i A') }}</div><div class="cell" data-l="Event">{{ $e->event }}</div>
<div class="cell"><span class="bd {{ $e->result==='success'?'ok':'bad' }}">{{ ucfirst($e->result) }}</span></div></div>
@empty<div class="empty">No lock activity yet.</div>@endforelse
</div>
<p class="note">You are notified only for repeated failed attempts and battery below 20%. The full log is available here on demand.</p>
<div class="demo"><span>Demo controls (simulated lock):</span>
<form method="post" action="{{ route('host.battery.set',$listing) }}">@csrf<label for="bt">Battery %</label><input id="bt" type="number" name="battery" min="0" max="100" value="{{ $listing->battery }}"><button class="btn">Set</button></form>
<form method="post" action="{{ route('host.lock.failed',$listing) }}">@csrf<button class="btn">Simulate wrong code</button></form></div>
@endif
@endsection
