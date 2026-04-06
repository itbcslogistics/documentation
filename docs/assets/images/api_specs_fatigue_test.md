# API Specification: Fatigue Test (Tes Kelelanan)

## Overview
Fatigue Test adalah fitur K3 (Keselamatan dan Kesehatan Kerja) yang mengukur tingkat kesiagaan karyawan sebelum bekerja. Tes ini wajib dilakukan saat **Clock-In** dan dapat diulang jika hasil pertama gagal.

### Test Components
1. **Memory Test**: 3 rounds, pattern recognition (3x3 grid)
2. **Sleep Survey**: Input jam tidur semalam
3. **Reaction Test**: Psychomotor Vigilance Task (PVT)

### Fatigue Levels
- **Normal** (Prima): Boleh bekerja tanpa pengawasan
- **Moderate** (Butuh Pengawasan): Boleh bekerja dengan pengawasan supervisor
- **Severe** (Bahaya): **TIDAK BOLEH BEKERJA**, harus istirahat 3-4 jam dan retry

---

## Endpoints

### 1. Submit Fatigue Test Result

**Endpoint:** `POST /api/fatigue-tests`

**Description:** Menyimpan hasil tes kelelahan karyawan. Dipanggil setelah karyawan menyelesaikan 3 tahap tes (Memory, Survey, Reaction).

**Authentication:** Required (Bearer Token)

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 123,
  "test_datetime": "2026-02-09T08:15:30Z",
  "memory_score": 2,
  "sleep_time": "23:30",
  "reaction_avg_ms": 520,
  "reaction_times": [510, 530, 520, 515, 525],
  "fatigue_level": "severe"
}
```

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | integer | ✅ | ID user/karyawan yang melakukan tes |
| `test_datetime` | string (ISO 8601) | ✅ | Waktu tes dilakukan (UTC) |
| `memory_score` | integer (0-3) | ✅ | Jumlah round memory test yang benar |
| `sleep_time` | string (HH:mm) | ✅ | Jam tidur semalam (24-hour format) |
| `reaction_avg_ms` | integer | ✅ | Rata-rata waktu reaksi dalam milidetik |
| `reaction_times` | array[integer] | ✅ | Array waktu reaksi per trial (ms) |
| `fatigue_level` | enum | ✅ | Hasil akhir: `normal`, `moderate`, `severe` |

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Hasil tes berhasil disimpan",
  "data": {
    "id": 789,
    "user_id": 123,
    "fatigue_level": "severe",
    "can_work": false,
    "retry_after": "2026-02-09T11:15:30Z",
    "created_at": "2026-02-09T08:15:30Z"
  }
}
``` 

...

**Change Log**

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-09 | Initial specification |
| 1.1 | 2026-02-09 | Updated `employee_id` to `user_id` to match backend implementation |

### 2. Get Today's Fatigue Test Status

**Endpoint:** `GET /api/fatigue-tests/today`

**Description:** Mengecek apakah karyawan sudah melakukan tes hari ini dan mendapatkan status terakhir.

**Authentication:** Required (Bearer Token)

**Response Body:**
```json
{
  "success": true,
  "data": {
    "has_tested_today": true,
    "latest_test": {
      "id": 123,
      "fatigue_level": "severe",
      "tested_at": "2026-02-09T08:15:30Z",
      "memory_score": 2,
      "reaction_avg_ms": 520
    },
    "can_work": false,
    "needs_retry": true,
    "can_retry_now": false,
    "retry_after": "2026-02-09T11:15:30Z",
    "retry_countdown_minutes": 144
  }
}
```

**Field Descriptions:**

| Field | Type | Description |
|-------|------|-------------|
| `has_tested_today` | boolean | Apakah sudah tes hari ini? |
| `can_work` | boolean | Apakah boleh bekerja? (Harus tidak null, default false) |
| `needs_retry` | boolean | Apakah perlu tes ulang? (Harus tidak null, default false) |
| `can_retry_now` | boolean | Apakah sudah bisa tes ulang sekarang? (Harus tidak null, default false) |
| `latest_test` | object/null | Detail tes terakhir jika ada |

---


## Contact

For questions or clarifications, contact:
- **Frontend Team**: [Your Team]
- **Backend Team**: [Backend Team]
- **Product Owner**: [PO Name]
