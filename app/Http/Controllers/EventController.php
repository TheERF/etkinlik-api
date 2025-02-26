<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::all(), 200);
    }

    public function show($id)
    {
        try {
            $event = Event::findOrFail($id);
            return response()->json($event, 200);
        } catch (\Exception $e) {
            Log::error('Event Show Error: ' . $e->getMessage());
            return response()->json(['error' => 'Event not found'], 404);
        }
    }

    public function store(Request $request)
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue_id' => 'required|integer|exists:venues,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|in:upcoming,ongoing,completed,canceled',
        ]);

        try {
            $event = Event::create($request->all());
            return response()->json(['message' => 'Event created successfully', 'event' => $event], 201);
        } catch (\Exception $e) {
            Log::error('Event Create Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create event'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $event = Event::findOrFail($id);
            $event->update($request->all());
            return response()->json(['message' => 'Event updated successfully', 'event' => $event], 200);
        } catch (\Exception $e) {
            Log::error('Event Update Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update event'], 500);
        }
    }

    public function destroy($id)
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $event = Event::findOrFail($id);
            $event->delete();
            return response()->json(['message' => 'Event deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Event Delete Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete event'], 500);
        }
    }
}
