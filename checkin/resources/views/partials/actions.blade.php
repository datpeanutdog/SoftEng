<form method="post" action="{{ route('bookings.update', [$b, 'approve']) }}">@csrf<button class="btn p">Approve</button></form>
<form method="post" action="{{ route('bookings.update', [$b, $act]) }}">@csrf<button class="btn">{{ ucfirst($act) }}</button></form>
