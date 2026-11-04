<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_registration_requires_a_philippine_mobile_number(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'password' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->postJson('/api/register', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'password' => 'secret123',
            'phone' => '+639171234567',
        ])->assertCreated()->assertJsonPath('user.phone', '+639171234567');
    }

    public function test_approval_is_blocked_until_an_admin_verifies_payment(): void
    {
        $guest = User::factory()->create(['role' => 'Guest', 'phone' => '+639171234567']);
        $host = User::factory()->create(['role' => 'Host']);
        $admin = User::factory()->create(['role' => 'Admin']);
        $booking = $this->booking($guest, $host, 'Pending');

        $this->actingAs($host)->postJson("/api/bookings/{$booking->id}/approve")
            ->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/bookings/{$booking->id}", ['status' => 'Approved'])
            ->assertUnprocessable();

        $this->actingAs($guest)->putJson("/api/bookings/{$booking->id}/payment", [
            'payment_method' => 'ewallet',
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_reference');

        $this->actingAs($host)->postJson("/api/admin/bookings/{$booking->id}/payment/verify")
            ->assertForbidden();

        $this->actingAs($guest)->putJson("/api/bookings/{$booking->id}/payment", [
            'payment_method' => 'ewallet',
            'payment_reference' => 'EW-12345',
        ])->assertOk()->assertJsonPath('payment_status', 'pending_verification')->assertJsonPath('total', 3000);

        $this->actingAs($host)->postJson("/api/bookings/{$booking->id}/approve")
            ->assertUnprocessable();
        $this->actingAs($admin)->postJson("/api/admin/bookings/{$booking->id}/payment/verify")
            ->assertOk()->assertJsonPath('payment_status', 'verified');

        Http::fake();
        config(['services.semaphore.key' => 'test-key']);
        $this->actingAs($host)->postJson("/api/bookings/{$booking->id}/approve")
            ->assertOk()->assertJsonPath('status', 'Approved')->assertJsonPath('decision_sms_status', 'sent');
        Http::assertSent(fn ($request) => str_contains($request['message'], 'was approved'));
    }

    public function test_admin_can_retry_a_failed_decision_sms(): void
    {
        Http::fakeSequence()->push([], 500)->push([], 200);
        config(['services.semaphore.key' => 'test-key']);
        $guest = User::factory()->create(['role' => 'Guest', 'phone' => '+639171234567']);
        $host = User::factory()->create(['role' => 'Host']);
        $admin = User::factory()->create(['role' => 'Admin']);
        $booking = $this->booking($guest, $host, 'Pending', 'verified');

        $this->actingAs($host)->postJson("/api/bookings/{$booking->id}/decline")
            ->assertOk()->assertJsonPath('decision_sms_status', 'failed')->assertJsonPath('sms.sent', false);
        $this->actingAs($admin)->postJson("/api/admin/bookings/{$booking->id}/sms/resend")
            ->assertOk()->assertJsonPath('decision_sms_status', 'sent')->assertJsonPath('sms.sent', true);
    }

    public function test_declining_a_paid_booking_marks_a_refund_due_and_notifies_guest(): void
    {
        Http::fake();
        config(['services.semaphore.key' => 'test-key']);
        $guest = User::factory()->create(['role' => 'Guest', 'phone' => '+639171234567']);
        $host = User::factory()->create(['role' => 'Host']);
        $admin = User::factory()->create(['role' => 'Admin']);
        $booking = $this->booking($guest, $host, 'Pending', 'verified');

        $this->actingAs($host)->postJson("/api/bookings/{$booking->id}/decline")
            ->assertOk()->assertJsonPath('status', 'Denied')->assertJsonPath('refund_status', 'due')
            ->assertJsonPath('sms.sent', true);
        Http::assertSent(fn ($request) => str_contains($request['message'], 'was denied'));

        $this->actingAs($admin)->postJson("/api/admin/bookings/{$booking->id}/refund/complete")
            ->assertOk()->assertJsonPath('refund_status', 'refunded');
    }

    private function booking(User $guest, User $host, string $status, string $paymentStatus = 'unpaid'): Booking
    {
        $listing = Listing::create([
            'host_id' => $host->id,
            'name' => 'Test stay',
            'type' => 'Hotel',
            'location' => 'Carmen',
            'price' => 1500,
            'status' => 'active',
        ]);

        return Booking::create([
            'guest_id' => $guest->id,
            'host_id' => $host->id,
            'listing_id' => $listing->id,
            'listing_title' => $listing->name,
            'guest_name' => $guest->name,
            'price' => $listing->price,
            'nights' => 2,
            'code' => '123456',
            'status' => $status,
            'payment_method' => $paymentStatus === 'unpaid' ? null : 'ewallet',
            'payment_reference' => $paymentStatus === 'unpaid' ? null : 'EW-12345',
            'payment_status' => $paymentStatus,
            'refund_status' => 'none',
            'check_in' => now()->addDays(2)->setTime(14, 0),
            'check_out' => now()->addDays(4)->setTime(11, 0),
        ]);
    }
}
