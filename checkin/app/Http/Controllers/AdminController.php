<?php
namespace App\Http\Controllers;

use App\Models\{Booking, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function pending()
    {
        return view('admin.pending', [
            'pending' => Booking::with(['guest', 'listing'])->where('status', 'pending')->oldest()->get(),
            'approved' => Booking::where('status', 'approved')->whereDate('decided_at', today())->count(),
            'cancelled' => Booking::whereIn('status', ['declined', 'cancelled'])->whereDate('decided_at', today())->count(),
        ]);
    }

    public function users() { return view('admin.users', ['users' => User::orderBy('id')->get()]); }
    public function editUser(User $user) { abort_if($user->role === 'admin', 403); return view('admin.user-form', compact('user')); }

    public function updateUser(Request $r, User $user)
    {
        abort_if($user->role === 'admin', 403);
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:guest,host',
        ]);
        $user->update($d);
        return redirect()->route('admin.users')->with('ok', 'User updated.');
    }

    public function setStatus(Request $r, User $user)
    {
        abort_if($user->role === 'admin', 403);
        $s = $r->validate(['status' => 'required|in:active,suspended'])['status'];
        $user->update(['status' => $s]);
        return back()->with('ok', $user->name . ($s === 'suspended' ? ' suspended.' : ' reactivated.'));
    }

    public function flagged()
    {
        $b = Booking::with(['guest', 'listing'])->where('status', 'flagged')->oldest()->get();
        return view('admin.flagged', [
            'bookings' => $b,
            'first' => $b->where('flag_reason', 'first_booking')->count(),
            'late' => $b->where('flag_reason', 'within_24h')->count(),
        ]);
    }
}
