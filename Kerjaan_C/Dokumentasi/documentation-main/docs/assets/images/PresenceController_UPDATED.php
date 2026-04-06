<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PresenceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

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
            'photo' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();

        if ($request->type === 'in') {
            return $this->handleClockIn($user, $request);
        } else {
            return $this->handleClockOut($user, $request);
        }
    }

    private function handleClockIn($user, $request)
    {
        // PERBAIKAN: Cek apakah ada sesi yang MASIH TERBUKA (tidak peduli tanggal)
        $openPresence = Presence::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->first();

        if ($openPresence) {
            $clockInTime = Carbon::parse($openPresence->date . ' ' . $openPresence->clock_in);
            $hoursElapsed = $clockInTime->diffInHours(now());

            return response()->json([
                'message' => 'Anda masih memiliki sesi aktif. Silakan clock out terlebih dahulu.',
                'open_session' => [
                    'date' => $openPresence->date,
                    'clock_in' => $openPresence->clock_in,
                    'hours_elapsed' => round($hoursElapsed, 1)
                ]
            ], 400);
        }

        // Buat presence baru
        $now = now();
        $presence = new Presence();
        $presence->user_id = $user->id;
        $presence->date = $now->format('Y-m-d');
        $presence->clock_in = $now->format('H:i:s');
        $presence->latitude_in = $request->latitude;
        $presence->longitude_in = $request->longitude;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('presences/in', 'public');
            $presence->face_photo_in = $path;
        }

        $presence->status = 'present';
        $presence->save();

        return response()->json([
            'message' => 'Clock in berhasil',
            'data' => $presence
        ], 201);
    }

    private function handleClockOut($user, $request)
    {
        // PERBAIKAN: Cari sesi TERBUKA terakhir (tidak peduli tanggal)
        $openPresence = Presence::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->first();

        if (!$openPresence) {
            return response()->json([
                'message' => 'Anda belum clock in'
            ], 400);
        }

        // Hitung durasi sejak clock in
        $clockInDateTime = Carbon::parse($openPresence->date . ' ' . $openPresence->clock_in);
        $now = now();
        $durationHours = $clockInDateTime->diffInHours($now, false); // false = signed difference

        // SOLUSI 3: Validasi bertingkat

        // KASUS 1: Durasi normal (0-12 jam) - Shift terpanjang
        if ($durationHours <= 12) {
            return $this->completeClockOut($openPresence, $request, 'present');
        }

        // KASUS 2: Durasi sangat lama (>24 jam) - Tolak total
        if ($durationHours > 24) {
            return response()->json([
                'message' => 'Sesi terlalu lama. Silakan ajukan koreksi presensi melalui menu Koreksi Kehadiran.',
                'clock_in_time' => $clockInDateTime->format('d M Y H:i'),
                'hours_elapsed' => round($durationHours, 1)
            ], 400);
        }

        // KASUS 3: Durasi menengah (12-24 jam) - Izinkan dengan warning
        $response = $this->completeClockOut($openPresence, $request, 'late_checkout');

        // Tambahkan warning ke response
        $responseData = $response->getData(true);
        $responseData['warning'] = true;
        $responseData['message'] = 'Clock out berhasil. Catatan: Anda clock out terlambat (' .
            round($durationHours, 1) . ' jam). Data akan direview oleh admin.';

        return response()->json($responseData, 200);
    }

    private function completeClockOut($presence, $request, $status)
    {
        $now = now();

        $presence->clock_out = $now->format('H:i:s');
        $presence->latitude_out = $request->latitude;
        $presence->longitude_out = $request->longitude;
        $presence->status = $status;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('presences/out', 'public');
            $presence->face_photo_out = $path;
        }

        $presence->save();

        return response()->json([
            'message' => 'Clock out berhasil',
            'data' => $presence
        ], 200);
    }
}
