# Airbnb Check-in System (Laravel)

Roles: Guest, Host, Admin. Guests request bookings; rules auto-flag risky ones for admin review;
hosts manage listings, approve requests and the (simulated) smart lock.

## Setup (once)
```
composer create-project laravel/laravel checkin
cd checkin
# copy everything from this folder into the checkin folder (merge, overwrite when asked)
php artisan migrate:fresh --seed
php artisan serve
```
Open http://127.0.0.1:8000. Demo accounts (password `password`):
admin@email.com (admin), lea@email.com (host), maria@email.com (guest).

## Flagging rules (app/Models/Booking.php, flagFor)
1. First booking on a new account.
2. Check-in within 24 hours of the request.
Flagged bookings can only be approved or cancelled by an admin.
