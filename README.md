# Gho Studio

Dashboard widyaiswara dengan tiga area: **Coach**, **Fasilitator**, dan **CMS** (Content Management System untuk media sosial), ditambah tampilan **Peserta**, untuk Latsar, PKP, PKA, dan PKN Tingkat II.

| Folder / file | Isi |
| --- | --- |
| `aplikasi/` | Aplikasi sungguhan (Laravel 13 + MySQL). Cara pasang: `aplikasi/PANDUAN-PASANG-HOSTINGER.md` |
| `SPESIFIKASI-GHO-STUDIO.md` | Spesifikasi lengkap |
| `prototipe/gho-studio-prototipe.html` | Prototipe klik (simulasi), buka langsung di browser |
| `HANDOFF.md` | Catatan serah terima untuk melanjutkan dari akun Claude lain |

## Yang sudah ada di aplikasi (versi 0.1)
- Satu akun pemilik: Super Admin + Coach + Fasilitator + CMS, pindah peran satu klik. Akun peserta terpisah.
- Struktur Pelatihan › Agenda › Mata Pelatihan › Aktivitas, jadwal buka–tutup per tingkat, kunci berurutan yang bisa dimatikan per item.
- Tautan bagikan per Pelatihan/Agenda/Mata Pelatihan/Aktivitas dengan waktu mulai dan akhir.
- Peserta masuk lewat persetujuan, undangan (email atau WhatsApp), atau unggah daftar Excel/CSV.
- Aktivitas: materi, video (tidak bisa dilompati), audio, eBook Heyzine (waktu baca minimal), tugas tulis dan refleksi (editor seperti Word dengan persamaan, unggah dokumen bisa dimatikan), studi kasus interaktif (jawaban disimpan dulu), kuis 6 bentuk dengan pembuat soal otomatis.
- Tanggapan dan nilai otomatis dengan rubrik, bisa ditahan untuk ditinjau coach/fasilitator.
- Laporan per Aktivitas, Mata Pelatihan, Agenda untuk peserta dan fasilitator, unduh CSV.
- CMS: konten, caption per platform, jadwal terbit, akun terhubung.
- Penyedia layanan cerdas bisa dipilih: DeepSeek, OpenAI, Gemini, Claude, OpenRouter, Groq, dan yang kompatibel OpenAI.
- Bahasa Indonesia dan Inggris.
