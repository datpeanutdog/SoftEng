<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DecisionSms
{
    public function send(Booking $booking): array
    {
        $apiKey = config('services.semaphore.key');
        $phone = $booking->guest?->phone;
        if (! $phone) {
            Log::warning('Booking decision SMS was not sent because the guest has no mobile number.', ['booking_id' => $booking->id]);
            $booking->update(['decision_sms_status' => 'failed']);

            return ['sent' => false, 'message' => 'SMS was not sent: this guest account has no mobile number.'];
        }
        if (! $apiKey) {
            Log::warning('Booking decision SMS was not sent because Semaphore is not configured.', ['booking_id' => $booking->id]);
            $booking->update(['decision_sms_status' => 'failed']);

            return ['sent' => false, 'message' => 'SMS was not sent: configure SEMAPHORE_API_KEY in the backend environment.'];
        }

        $decision = match ($booking->status) {
            'Approved' => 'approved',
            'Denied' => 'denied',
            'Cancelled' => 'cancelled',
            default => strtolower($booking->status),
        };

        try {
            $payload = [
                'apikey' => $apiKey,
                'number' => $phone,
                'message' => "LockBay: Your booking #{$booking->id} for {$booking->listing_title} was {$decision}.",
            ];
            if (config('services.semaphore.sender_name')) {
                $payload['sendername'] = config('services.semaphore.sender_name');
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post(config('services.semaphore.endpoint'), $payload);
            $response->throw();
            $booking->update(['decision_sms_status' => 'sent']);

            return ['sent' => true, 'message' => 'SMS notification accepted by Semaphore.'];
        } catch (ConnectionException|RequestException $e) {
            $booking->update(['decision_sms_status' => 'failed']);
            Log::error('Booking decision SMS could not be sent.', [
                'booking_id' => $booking->id,
                'http_status' => isset($response) ? $response->status() : null,
            ]);

            return ['sent' => false, 'message' => 'The booking was updated, but its SMS notification could not be sent. Check the Semaphore configuration and try again.'];
        }
    }
}
