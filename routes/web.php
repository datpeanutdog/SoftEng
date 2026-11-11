<?php
use App\Http\Controllers\{AdminController, AuthController, BookingController, GuestController, HostController};
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) return redirect()->route('login');
    return redirect()->route(match (auth()->user()->role) {
        'host' => 'host.dashboard', 'admin' => 'admin.pending', default => 'guest.home',
    });
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Guest
Route::middleware(['auth', EnsureRole::class . ':guest'])->prefix('guest')->name('guest.')->group(function () {
    Route::get('/', [GuestController::class, 'home'])->name('home');
    Route::post('/bookings', [GuestController::class, 'store'])->name('bookings.store');
});

// Host
Route::middleware(['auth', EnsureRole::class . ':host'])->prefix('host')->name('host.')->group(function () {
    Route::get('/', [HostController::class, 'dashboard'])->name('dashboard');
    Route::get('/listings', [HostController::class, 'listings'])->name('listings.index');
    Route::get('/listings/create', [HostController::class, 'create'])->name('listings.create');
    Route::post('/listings', [HostController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [HostController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [HostController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [HostController::class, 'destroy'])->name('listings.destroy');
    Route::get('/doorlock', [HostController::class, 'doorlock'])->name('doorlock');
    Route::post('/access/{booking}/remove', [HostController::class, 'removeAccess'])->name('access.remove');
    Route::post('/doorlock/{listing}/battery-replaced', [HostController::class, 'replaceBattery'])->name('battery.replaced');
    Route::post('/doorlock/{listing}/battery', [HostController::class, 'setBattery'])->name('battery.set');
    Route::post('/doorlock/{listing}/failed-attempt', [HostController::class, 'failedAttempt'])->name('lock.failed');
});

// Admin
Route::middleware(['auth', EnsureRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'pending'])->name('pending');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/status', [AdminController::class, 'setStatus'])->name('users.status');
    Route::get('/flagged', [AdminController::class, 'flagged'])->name('flagged');
});

// Approve / decline / cancel (hosts: own pending bookings; admins: any pending or flagged)
Route::post('/bookings/{booking}/{action}', [BookingController::class, 'update'])
    ->whereIn('action', ['approve', 'decline', 'cancel'])
    ->middleware(['auth', EnsureRole::class . ':host,admin'])->name('bookings.update');
