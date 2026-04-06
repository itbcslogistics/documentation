# Masalah Timezone Backend - Solusi

## 🔍 Akar Masalah

User clock in: **19 Des 2025, 10:45 WIB (GMT+7)**

Backend menyimpan:
```json
{
  "date": "2025-12-18T17:00:00.000000Z",  // ❌ Salah!
  "clock_in": "10:45:25"
}
```

**Kenapa mundur 1 hari?**
- WIB = GMT+7
- 19 Des 2025, 00:00 WIB = 18 Des 2025, 17:00 UTC
- Laravel mengkonversi tanggal lokal ke UTC → mundur 7 jam → tanggal berubah!

## ✅ Solusi Backend (Laravel)

### 1. **Update `config/app.php`**

Pastikan timezone aplikasi sudah benar:

```php
'timezone' => 'Asia/Jakarta',  // WIB (GMT+7)
```

### 2. **Update Migration (Jika Belum)**

Field `date` harus tipe `DATE`, bukan `DATETIME` atau `TIMESTAMP`:

```php
Schema::create('presences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->date('date');  // ✅ Gunakan DATE, bukan DATETIME
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    // ...
});
```

### 3. **Update Model `Presence.php`**

Tambahkan cast untuk field `date`:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'status',
        'face_photo_in',
        'face_photo_out',
    ];

    protected $casts = [
        'date' => 'date',  // ✅ Cast sebagai date (bukan datetime)
    ];
}
```

### 4. **Update Controller**

Pastikan menyimpan tanggal sebagai string `Y-m-d`, bukan Carbon instance:

```php
private function handleClockIn($user, $request)
{
    $now = now(); // Carbon instance dengan timezone Asia/Jakarta
    
    $presence = new Presence();
    $presence->user_id = $user->id;
    $presence->date = $now->format('Y-m-d');  // ✅ String format, bukan Carbon
    $presence->clock_in = $now->format('H:i:s');
    $presence->latitude_in = $request->latitude;
    $presence->longitude_in = $request->longitude;
    $presence->status = 'present';
    $presence->save();

    return response()->json([
        'message' => 'Clock in berhasil',
        'data' => $presence
    ], 201);
}
```

### 5. **API Resource (Opsional tapi Recommended)**

Buat Resource untuk memastikan format response konsisten:

```php
// app/Http/Resources/PresenceResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PresenceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => $this->date->format('Y-m-d'),  // ✅ Format eksplisit
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'latitude_in' => $this->latitude_in,
            'longitude_in' => $this->longitude_in,
            'latitude_out' => $this->latitude_out,
            'longitude_out' => $this->longitude_out,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

Lalu gunakan di Controller:

```php
use App\Http\Resources\PresenceResource;

public function index(Request $request)
{
    $user = $request->user();
    
    $history = Presence::where('user_id', $user->id)
        ->orderBy('date', 'desc')
        ->orderBy('clock_in', 'desc')
        ->paginate(10);

    return PresenceResource::collection($history);
}

private function handleClockIn($user, $request)
{
    // ... (create presence)
    
    return response()->json([
        'message' => 'Clock in berhasil',
        'data' => new PresenceResource($presence)
    ], 201);
}
```

## 🧪 Testing

Setelah update backend:

1. **Hapus data lama** (atau update manual di database):
   ```sql
   DELETE FROM presences WHERE date < '2025-12-19';
   ```

2. **Test Clock In baru**:
   - Clock in jam 10:00 WIB tanggal 19 Des
   - Cek database: `date` harus `2025-12-19` (bukan `2025-12-18T...`)
   - Cek response API: `"date": "2025-12-19"` (bukan ISO timestamp)

3. **Restart Flutter app** untuk fetch data baru

## 📋 Checklist

- [ ] Update `config/app.php` → timezone `Asia/Jakarta`
- [ ] Update migration → field `date` tipe `DATE`
- [ ] Update model → cast `'date' => 'date'`
- [ ] Update controller → simpan sebagai `format('Y-m-d')`
- [ ] (Optional) Buat `PresenceResource` untuk format response
- [ ] Test clock in baru
- [ ] Verify di database dan API response

## ⚠️ Catatan Penting

**Frontend TIDAK perlu diubah lagi!** Kode Flutter sudah benar. Masalahnya 100% di backend yang mengirim tanggal dengan timezone UTC yang salah.
