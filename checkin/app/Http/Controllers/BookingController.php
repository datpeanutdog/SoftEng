<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /** Approve / decline (host) or approve / cancel (admin) a booking. */
    public function update(Request $r, Booking $booking, string $action)
    {
        $user = $r->user();
        abort_if($user->role === 'host' && $booking->listing->host_id !== $user->id, 403);
        abort_unless(in_array($booking->status, ['pending', 'flagged'], true), 422);
        abort_if($booking->status === 'flagged' && $user->role !== 'admin', 403); // flagged = admin only

        if ($action === 'approve') {
            $booking->approve();
            $msg = 'approved';
        } else {
            $booking->reject($action === 'decline' ? 'declined' : 'cancelled');
            $msg = $action === 'decline' ? 'declined' : 'cancelled';
        }
        return back()->with('ok', "Booking #{$booking->id} {$msg}.");
    }
}
