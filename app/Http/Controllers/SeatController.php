<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seat;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SeatController extends Controller
{
    /**
     * Belirli bir etkinlik için mevcut koltukları getir.
     */
    public function getSeatsByEvent($event_id)
    {
        try {
            $event = Event::findOrFail($event_id);
            $seats = Seat::where('venue_id', $event->venue_id)->get();

            return response()->json($seats, 200);
        } catch (\Exception $e) {
            Log::error('Get Seats By Event Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve seats'], 500);
        }
    }

    /**
     * Belirli bir mekan (venue) için koltukları getir.
     */
    public function getSeatsByVenue($venue_id)
    {
        try {
            $seats = Seat::where('venue_id', $venue_id)->get();

            if ($seats->isEmpty()) {
                return response()->json(['message' => 'No seats found for this venue'], 404);
            }

            return response()->json($seats, 200);
        } catch (\Exception $e) {
            Log::error('Get Seats By Venue Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve seats'], 500);
        }
    }

    /**
     * Koltukları belirli bir süreliğine blokla.
     */
    public function blockSeats(Request $request)
    {
        $request->validate([
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'exists:seats,id',
            'event_id' => 'required|exists:events,id',
        ]);

        try {
            $event = Event::findOrFail($request->event_id);

            $alreadyReserved = ReservationItem::whereIn('seat_id', $request->seat_ids)
                ->whereHas('reservation', function ($query) use ($request) {
                    $query->where('event_id', $request->event_id)
                        ->where('status', 'pending');
                })->exists();

            if ($alreadyReserved) {
                return response()->json(['message' => 'Some seats are already reserved'], 409);
            }

            DB::transaction(function () use ($request) {
                $reservation = Reservation::create([
                    'user_id' => auth()->id(),
                    'event_id' => $request->event_id,
                    'status' => 'pending',
                    'total_amount' => 0,
                    'expires_at' => Carbon::now()->addMinutes(15),
                ]);

                $seats = Seat::whereIn('id', $request->seat_ids)->get();
                $totalAmount = $seats->sum('price');

                foreach ($seats as $seat) {
                    ReservationItem::create([
                        'reservation_id' => $reservation->id,
                        'seat_id' => $seat->id,
                        'price' => $seat->price,
                    ]);
                }

                $reservation->update(['total_amount' => $totalAmount]);
            });

            return response()->json(['message' => 'Seats blocked successfully'], 201);
        } catch (\Exception $e) {
            Log::error('Block Seats Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to block seats'], 500);
        }
    }

    /**
     * Bloklanmış koltukları serbest bırak.
     */
    public function releaseSeats(Request $request)
    {
        $request->validate([
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                ReservationItem::whereIn('seat_id', $request->seat_ids)
                    ->whereHas('reservation', function ($query) {
                        $query->where('status', 'pending')
                            ->where('expires_at', '<', Carbon::now());
                    })
                    ->delete();
            });

            return response()->json(['message' => 'Seats released successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Release Seats Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to release seats'], 500);
        }
    }
}
