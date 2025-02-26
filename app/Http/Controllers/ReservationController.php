<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $reservations = Reservation::where('user_id', $request->user()->id)
                ->with('items.seat')
                ->get();

            return response()->json($reservations, 200);
        } catch (\Exception $e) {
            Log::error('Reservation List Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve reservations'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        try {
            $totalAmount = Seat::whereIn('id', $request->seat_ids)->sum('price');

            DB::transaction(function () use ($request, $totalAmount) {
                $reservation = Reservation::create([
                    'user_id' => auth()->id(),
                    'event_id' => $request->event_id,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                    'expires_at' => Carbon::now()->addMinutes(15),
                ]);

                foreach ($request->seat_ids as $seat_id) {
                    ReservationItem::create([
                        'reservation_id' => $reservation->id,
                        'seat_id' => $seat_id,
                        'price' => Seat::find($seat_id)->price,
                    ]);
                }
            });

            return response()->json(['message' => 'Reservation created successfully'], 201);
        } catch (\Exception $e) {
            Log::error('Reservation Create Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reservation'], 500);
        }
    }

    public function confirm($id)
    {
        try {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found or already confirmed'], 404);
            }

            $reservation->update(['status' => 'confirmed']);

            return response()->json(['message' => 'Reservation confirmed successfully']);
        } catch (\Exception $e) {
            Log::error('Reservation Confirm Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to confirm reservation'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found'], 404);
            }

            DB::transaction(function () use ($reservation) {
                ReservationItem::where('reservation_id', $reservation->id)->delete();
                $reservation->delete();
            });

            return response()->json(['message' => 'Reservation canceled successfully']);
        } catch (\Exception $e) {
            Log::error('Reservation Cancel Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel reservation'], 500);
        }
    }
}
