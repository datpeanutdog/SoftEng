@extends('layouts.app')
@section('title', $listing->exists ? 'Edit listing' : 'Add listing')
@section('content')
<h1>{{ $listing->exists ? 'Edit listing' : 'Add listing' }}</h1><p class="sub">{{ $listing->exists ? 'Update the details for this property.' : 'Add a property guests can request.' }}</p>
<form class="fm" method="post" action="{{ $listing->exists ? route('host.listings.update',$listing) : route('host.listings.store') }}">@csrf @if($listing->exists) @method('PUT') @endif
<label>Listing name<input name="name" value="{{ old('name',$listing->name) }}" required></label>
<label>Price per night (USD)<input type="number" min="1" name="price" value="{{ old('price',$listing->price) }}" required></label>
<label>Available from<input type="date" name="available_from" value="{{ old('available_from',$listing->available_from?->toDateString()) }}"></label>
<label>Available until<input type="date" name="available_to" value="{{ old('available_to',$listing->available_to?->toDateString()) }}"></label>
<label>Status<select name="status"><option value="active" @selected(old('status',$listing->status)==='active')>Active</option><option value="paused" @selected(old('status',$listing->status)==='paused')>Paused</option></select></label>
<div class="row"><a class="btn" href="{{ route('host.listings.index') }}">Cancel</a><button class="btn p">{{ $listing->exists ? 'Save changes' : 'Add listing' }}</button></div>
</form>
@endsection
