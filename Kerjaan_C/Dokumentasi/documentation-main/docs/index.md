---
id: intro
title: Panduan Pengguna 
sidebar_position: 1
---

# Panduan Pengguna Aplikasi Presensi

Selamat datang di Dokumentasi Resmi Aplikasi Presensi Karyawan. 
Dokumen ini disusun untuk memandu karyawan dalam menggunakan seluruh fitur aplikasi mulai dari login hingga pengajuan cuti.

Markdown ini disusun sedemikian rupa agar kompatibel dengan **Docusaurus** atau **MkDocs** untuk proses publikasi otomatis ke Netlify.

---

## 📚 Daftar Isi / Struktur Navigasi (Sidebar)

Rencana struktur *sidebar* pada website dokumentasi:

1. **Memulai (Getting Started)**
   - Login & Aktivasi Akun
   - Pengaturan Keamanan (Sidik Jari / Pemindai Wajah / PIN)
   - Lupa Kata Sandi & Lupa PIN

2. **Absensi Harian (Daily Attendance)**
   - Cara Absen Masuk (Clock-In) dan Verifikasi Lokasi
   - Indikator "Total Jam Kerja" (Live Timer)
   - Tes Tingkat Kelelahan (Khusus Operator)
   - Cara Absen Keluar (Clock-Out) & Offline Mode (Absen Tanpa Sinyal)

3. **Menu Profil & Informasi Karyawan**
   - Melihat Slip Gaji Akhir Bulan
   - Rekap Kehadiran & Grafik Kinerja Bulanan
   - Update Informasi & Ganti PIN

4. **Pengajuan (Requests & Approvals)**
   - Mengajukan Izin & Cuti
   - Mengajukan Tugas Luar Kota
   - Cek Status Approval (Riwayat Pengajuan)

5. **Kendala & Solusi (Troubleshooting/FAQ)**
   - *Aplikasi menampilkan "Device Sudah Terdaftar"*
   - *Gagal memindai Sidik Jari/FaceID*
   - *Error: GPS / Lokasi Tidak Ditemukan*
   - *Data absen belum masuk setelah fitur Offline Mode*

---

## 🛠 Konfigurasi Netlify (Rencana Deploy)

Untuk menerbitkan dokumentasi ini via **Netlify**, Anda perlu menginisialisasi Docusaurus atau MkDocs di folder ini.
Jika sudah setuju dengan struktur di atas, langkah selanjutnya adalah:
1. Jalankan `npx create-docusaurus@latest website classic` atau gunakan Python MkDocs.
2. Pindahkan naskah markdown (\`.md\`) masing-masing bab ke folder `docs/` di generator tersebut.
3. Hubungkan repositori GitHub/GitLab ke Netlify dengan *Build Command*: `npm run build` dan *Publish Directory*: `build`.
