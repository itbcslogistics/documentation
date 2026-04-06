<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PresenceController extends Controller
{
    public function index(Request $request)
    {
        // History presensi
        // User (MPresensi) from connection pgsql_master via Sanctum
        $user = $request->user();

        // Get presences from presensi_db
        // Assuming we join or just filter by user_id
        $history = Presence::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->paginate(10);

        return response()->json($history);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'nullable|image|max:2048', // Base64 or file? Assuming file upload for now, or base64 handling needed. 
            // Docs say "face_photo_in". Mobile app usually sends multipart/form-data.
        ]);

        $user = $request->user();
        $date = now()->format('Y-m-d');
        $time = now()->format('H:i:s');

        // Check existing presence for today
        $presence = Presence::where('user_id', $user->id)->where('date', $date)->first();

        if ($request->type === 'in') {
            if ($presence && $presence->clock_in) {
                return response()->json(['message' => 'Anda sudah melakukan clock in hari ini'], 400);
            }

            if (!$presence) {
                $presence = new Presence();
                $presence->user_id = $user->id;
                $presence->date = $date;
            }

            $presence->clock_in = $time;
            $presence->latitude_in = $request->latitude;
            $presence->longitude_in = $request->longitude;

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('presences/in', 'public');
                $presence->face_photo_in = $path;
            }

            $presence->status = 'present'; // Simple logic
            $presence->save();

            return response()->json(['message' => 'Clock In Berhasil', 'data' => $presence]);

        } elseif ($request->type === 'out') {
            // Untuk shift malam yang melewati tengah malam, cari presence yang belum clock out
            // dari hari ini ATAU kemarin (dalam 24 jam terakhir)
            $yesterday = now()->subDay()->format('Y-m-d');

            $presence = Presence::where('user_id', $user->id)
                ->whereIn('date', [$date, $yesterday])
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->orderBy('date', 'desc')
                ->first();

            if (!$presence) {
                return response()->json(['message' => 'Anda belum clock in atau sudah clock out'], 400);
            }

            // Validasi: clock out maksimal 24 jam dari clock in
            $clockInDateTime = \Carbon\Carbon::parse($presence->date . ' ' . $presence->clock_in);
            $now = now();

            if ($now->diffInHours($clockInDateTime) > 24) {
                return response()->json(['message' => 'Clock out melebihi batas waktu (24 jam dari clock in)'], 400);
            }

            $presence->clock_out = $time;
            $presence->latitude_out = $request->latitude;
            $presence->longitude_out = $request->longitude;

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('presences/out', 'public');
                $presence->face_photo_out = $path;
            }

            $presence->save();

            return response()->json(['message' => 'Clock Out Berhasil', 'data' => $presence]);
        }
    }
}
