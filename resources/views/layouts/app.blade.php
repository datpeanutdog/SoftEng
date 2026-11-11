@php
$u = auth()->user();
$nav = match ($u->role) {
    'host' => [['host.dashboard','Dashboard','Dashboard','host.dashboard'],['host.listings.index','Listings','Listings','host.listings.*'],['host.doorlock','Doorlock','Doorlock','host.doorlock']],
    'admin' => [['admin.pending','Pending bookings','Pending','admin.pending'],['admin.users','Users','Users','admin.users*'],['admin.flagged','Flagged bookings','Flagged','admin.flagged']],
    default => [['guest.home','Stays','Stays','guest.home']],
};
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title') - Airbnb Check-in System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body><div class="app">
<aside class="side"><div class="brand">Airbnb Check-in System</div><div class="role">{{ ucfirst($u->role) }}</div>
<nav>@foreach($nav as $n)<a href="{{ route($n[0]) }}" class="{{ request()->routeIs($n[3]) ? 'on' : '' }}">{{ $n[1] }}</a>@endforeach</nav>
<div class="sw"><span class="who">{{ $u->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="lnk">Log out</button></form></div></aside>
<header class="bar"><span class="brand">Check-in</span><form method="post" action="{{ route('logout') }}">@csrf<button class="lnk">{{ ucfirst($u->role) }}: log out</button></form></header>
<main>
@if(session('ok'))<div class="flash" role="status">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="flash bad" role="alert">{{ $errors->first() }}</div>@endif
@yield('content')
</main>
<nav class="bnav">@foreach($nav as $n)<a href="{{ route($n[0]) }}" class="{{ request()->routeIs($n[3]) ? 'on' : '' }}">{{ $n[2] }}</a>@endforeach</nav>
</div></body></html>
