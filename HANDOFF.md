# Catatan serah terima (untuk melanjutkan dari akun Claude lain)

Gunakan file ini bila kuota satu langganan Claude habis dan pekerjaan dilanjutkan dari langganan lain.

## Cara melanjutkan
1. Buka Claude di akun lain, lalu hubungkan GitHub ke repo `disdiksumut123-lang/gho-studio` (atau unduh repo ini).
2. Berikan pesan pembuka: "Baca README.md, HANDOFF.md, dan SPESIFIKASI-GHO-STUDIO.md di repo gho-studio, lalu lanjutkan pekerjaan dari bagian 'Langkah berikutnya'."
3. Untuk mulai membangun aplikasi: unggah `SPESIFIKASI-GHO-STUDIO.md` dan minta Claude membangun Tahap 1 sesuai bab 21.

## Status per 14 November 2026
- Spesifikasi versi 2 selesai (`SPESIFIKASI-GHO-STUDIO.md`).
- Prototipe klik v0.2 selesai (`prototipe/gho-studio-prototipe.html`), sudah diuji di desktop (1360 px) dan HP (375 px).
- Tiga area: **Coach**, **Fasilitator**, **CMS** (Content Management System untuk media sosial), ditambah tampilan **Peserta**.

## Keputusan pemilik produk yang wajib dijaga
- Coach hanya: Rancangan Aktualisasi (Latsar), Aksi Perubahan (PKP, PKA), Proyek Perubahan (PKN Tk II), Learning journal (PKP, PKA, PKN), Lesson learned studi lapangan (PKP, PKA), Lesson learned (PKN), plus fitur khas coach (sesi GROW, catatan tiga arah, milestone dan peringatan dini, kartu progres mentor, latihan seminar). Semua aktivitas lain milik Fasilitator.
- Tidak boleh ada kata "AI" di nama menu atau aktivitas, walaupun AI bekerja di balik layar.
- Struktur: Pelatihan › Agenda › Mata Pelatihan › Aktivitas. Tiap tingkat punya jadwal; bawaannya berurutan; keterkaitan bisa dimatikan per item.
- Video, audio, eBook (Heyzine) wajib selesai sebelum aktivitas berikutnya terbuka.
- Kuis dalam 10 bentuk (pilihan ganda biasa, kompleks, benar/salah per pernyataan, menjodohkan, mengurutkan, isian, berbasis gambar, beralasan, skala sikap, kuis langsung berwaktu).
- Unggah dokumen opsional per aktivitas; bisa dimatikan sehingga peserta wajib mengetik. Editor seperti Word dengan persamaan.
- Studi kasus interaktif satu alur; setiap jawaban disimpan ke database sebelum tanggapan dibuat.
- CMS: unggah sekali dari Gho Studio, terbit di YouTube, Facebook, Instagram, TikTok, dan lainnya. Posting langsung di Facebook tidak bisa diteruskan otomatis ke TikTok.
- Tampilan mudah, kekinian, responsif; navigasi HP maksimal 4 menu + Lainnya.
- Aplikasi lama yang diadopsi: coach.gho-elearning.com dan Coach Labs (coach-labs.gho-elearning.com). Kredensial tidak disimpan di repo; minta langsung ke pemilik produk bila perlu.

## Langkah berikutnya
- Pemilik produk meninjau prototipe dan spesifikasi.
- Pilih hosting penyimpanan objek (misalnya Cloudflare R2) dan opsi media sosial (agregator atau API resmi).
- Bangun Tahap 1 (bab 21 spesifikasi).
