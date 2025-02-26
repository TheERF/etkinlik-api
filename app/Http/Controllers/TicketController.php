<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Reservation;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Kullanıcının tüm biletlerini getir.
     */
    public function index(Request $request)
    {
        try {
            $tickets = Ticket::whereHas('reservation', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->with(['seat', 'reservation.event'])->get();

            return response()->json($tickets, 200);
        } catch (\Exception $e) {
            Log::error('Ticket List Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve tickets'], 500);
        }
    }

    /**
     * Belirli bir bileti getir (Sadece sahibi görebilir).
     */
    public function show($id, Request $request)
    {
        try {
            $ticket = Ticket::whereHas('reservation', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->with(['seat', 'reservation.event'])->find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            return response()->json($ticket, 200);
        } catch (\Exception $e) {
            Log::error('Ticket Show Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve ticket'], 500);
        }
    }

    /**
     * Bileti PDF olarak indir (Etkinlik başlamadan önce).
     */
    public function download($id, Request $request)
    {
        try {
            $ticket = Ticket::whereHas('reservation', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->with(['seat', 'reservation.event'])->find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            if (Carbon::now()->greaterThanOrEqualTo($ticket->reservation->event->start_date)) {
                return response()->json(['message' => 'Ticket download is not allowed after event start'], 403);
            }

            $pdf = Pdf::loadView('pdf.ticket', ['ticket' => $ticket]);

            return $pdf->download('ticket-' . $ticket->ticket_code . '.pdf');
        } catch (\Exception $e) {
            Log::error('Ticket Download Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to download ticket'], 500);
        }
    }

    /**
     * Bileti başka bir kullanıcıya transfer et (Sadece kullanılmamış biletler).
     */
    public function transfer(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $ticket = Ticket::whereHas('reservation', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->where('status', 'valid')->find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found or already used'], 404);
            }

            $newOwner = User::where('email', $request->email)->first();

            if ($newOwner->id == $request->user()->id || $newOwner->id == $ticket->reservation->event->user_id) {
                return response()->json(['message' => 'Invalid ticket transfer'], 403);
            }

            DB::transaction(function () use ($ticket, $newOwner) {
                $ticket->reservation->update(['user_id' => $newOwner->id]);
            });

            return response()->json(['message' => 'Ticket transferred successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Ticket Transfer Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to transfer ticket'], 500);
        }
    }

    /**
     * Rezervasyon sonrası biletleri oluştur (Benzersiz kod üretir).
     */
    public static function generateTickets($reservationId)
    {
        try {
            $reservation = Reservation::find($reservationId);
            $event = Event::find($reservation->event_id);

            if (!$reservation || !$event) {
                return;
            }

            $now = Carbon::now();

            foreach ($reservation->items as $item) {
                $uniqueCode = strtoupper(Str::random(10));

                while (Ticket::where('ticket_code', $uniqueCode)->exists()) {
                    $uniqueCode = strtoupper(Str::random(10));
                }

                Ticket::create([
                    'reservation_id' => $reservation->id,
                    'seat_id' => $item->seat_id,
                    'ticket_code' => $uniqueCode,
                    'status' => 'valid',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ticket Generation Error: ' . $e->getMessage());
        }
    }

    /**
     * Etkinlik başlangıcından 24 saat önce bilet iptali yapılabilir.
     */
    public function cancelTicket($id, Request $request)
    {
        try {
            $ticket = Ticket::whereHas('reservation', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->where('status', 'valid')->find($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found or already used'], 404);
            }

            if (Carbon::now()->greaterThanOrEqualTo($ticket->reservation->event->start_date->subHours(24))) {
                return response()->json(['message' => 'Ticket cancellation period has expired'], 403);
            }

            $ticket->update(['status' => 'canceled']);

            return response()->json(['message' => 'Ticket canceled successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Ticket Cancel Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel ticket'], 500);
        }
    }
}
