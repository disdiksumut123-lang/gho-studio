# Spesifikasi Aplikasi Gho Studio

Versi 2.0 · 14 November 2026 · Pemilik produk: Dr. Hironymus Ghodang, M.Si., M.Psi (Widyaiswara)

Dokumen ini adalah spesifikasi lengkap untuk membangun **Gho Studio**, dashboard widyaiswara dengan tiga area: **Coach**, **Fasilitator**, dan **CMS** (Content Management System untuk media sosial), untuk Pelatihan Dasar CPNS (Latsar), Pelatihan Kepemimpinan Pengawas (PKP), Pelatihan Kepemimpinan Administrator (PKA), dan Pelatihan Kepemimpinan Nasional Tingkat II (PKN Tk II).

Dokumen ini ditulis supaya bisa langsung diunggah ke Claude (atau diberikan ke programmer) sebagai acuan membangun aplikasi. Prototipe klik tersedia di `prototipe/gho-studio-prototipe.html` (buka di browser). Semua respon otomatis di prototipe masih simulasi.

---

## Daftar isi

1. Ringkasan produk
2. Aturan wajib
3. Peran pengguna dan hak akses
4. Struktur kelas: Pelatihan › Agenda › Mata Pelatihan › Aktivitas
5. Navigasi dan peta layar
6. Katalog aktivitas
7. Rancangan bertahap: sistematika 4 pelatihan
8. Fitur peran Coach
9. Fitur peran Fasilitator
10. Editor jawaban dan unggah dokumen
11. Materi wajib: video, audio, eBook Heyzine
12. Kuis dalam 10 bentuk
13. Studi kasus interaktif
14. CMS: Content Management System (media sosial)
15. Fitur peserta
16. Kecerdasan buatan di balik layar
17. Struktur database
18. API
19. Arsitektur dan teknologi
20. Kebutuhan non-fungsional
21. Tahapan pembangunan dan kriteria selesai
22. Lampiran A: daftar pertanyaan sistematika
23. Lampiran B: struktur output rancangan

---

## 1. Ringkasan produk

Gho Studio menyatukan dua sistem yang sudah dipakai pemilik produk, lalu menambahkan banyak fitur baru:

- **Form coaching Gho** di coach.gho-elearning.com: peserta mengisi pertanyaan per bagian, lalu sistem menyusun Rancangan Aktualisasi (Latsar), Rancangan Aksi Perubahan (PKP, PKA), atau Rancangan Proyek Perubahan (PKN Tk II) sesuai sistematika.
- **Coach Labs** di coach-labs.gho-elearning.com: platform pembelajaran daring dengan jalur, modul, 20 jenis aktivitas, kuis, kelas, lembaga, harga, dan pembayaran. **Semua aktivitas Coach Labs diadopsi.**

Masalah yang diselesaikan:

1. Jawaban peserta pernah hilang (studi kasus 2 tahap: jawaban tahap 1 tidak tersimpan di database).
2. Di Coach Labs, kecerdasan buatan hanya membantu penyusun konten. Jawaban peserta belum ditanggapi.
3. Peran fasilitator (menyusun modul, bahan tayang, eBook, audiobook, menjalankan kelas, publikasi media sosial) belum punya alat.
4. Coach butuh alat pendampingan yang lebih dari sekadar form.
5. Server pemilik produk harus tetap ringan.

Prinsip produk:

- **Kecerdasan buatan bekerja di semua titik, tetapi namanya tidak tampil.** Sistem merancang penugasan, menanggapi setiap jawaban, menyiapkan draf respon coach dan fasilitator, dan menghasilkan output. Di antarmuka cukup disebut "Tanggapan", "Cek mutu", "Susun rancangan", "Perancang", dan sejenisnya.
- **Sangat mudah dipakai, tampilan kekinian, responsif.** Navigasi dikelompokkan, maksimal 4 menu utama di HP ditambah tombol "Lainnya".

---

## 2. Aturan wajib

1. **Tidak ada kata "AI" pada nama menu, nama aktivitas, tombol, label, maupun judul halaman.** Contoh: "Perancang" (bukan Perancang AI), "Kuis" (bukan Kuis AI), "Tanggapan" (bukan Respon AI), "Cek mutu" (bukan Cek mutu AI). Ikon bintang kecil (spark) boleh dipakai sebagai penanda isi yang dibuat otomatis.
2. **Simpan dulu, baru panggil kecerdasan buatan.** Setiap jawaban peserta harus tersimpan dan dikonfirmasi database (respons 2xx dengan id baris) sebelum permintaan tanggapan dikirim. Jika tanggapan gagal, jawaban tetap ada dan peserta bisa meminta tanggapan ulang tanpa menulis ulang.
3. **Tidak menimpa riwayat.** Jawaban per putaran, per bagian, dan per versi disimpan sebagai baris baru (append-only).
4. **Simpan otomatis.** Isian disimpan 2 sampai 3 detik setelah berhenti mengetik (debounce), dengan cadangan di localStorage atau IndexedDB. Saat offline, antrean disimpan lokal dan dikirim ulang otomatis. Indikator selalu terlihat: "Menyimpan…", "Tersimpan 10.42", "Offline, akan dikirim saat tersambung".
5. **Unggah dokumen bersifat opsional per aktivitas.** Fasilitator atau coach menyalakan atau mematikan izin unggah untuk setiap aktivitas. Jika dimatikan, peserta wajib mengetik langsung di editor. File tidak disimpan di server aplikasi, tetapi langsung ke penyimpanan objek (lihat bab 10). CMS (bab 14) memakai unggahan media milik pemilik akun.
6. **Kunci API tidak pernah dikirim ke browser.** Semua panggilan kecerdasan buatan dan API media sosial lewat server.
7. **Ekspor file dibuat di browser.** Word (.docx), PowerPoint (.pptx), Excel/CSV dirakit di perangkat pengguna.
8. **Bahasa Indonesia.** Sapaan peserta Latsar "kamu/Adik-Adik CPNS", peserta PKP/PKA/PKN "Bapak/Ibu".
9. **Responsif.** Nyaman di HP lebar 360 px sampai layar lebar.
10. **Berurutan tetapi dinamis.** Item di setiap tingkat struktur kelas bawaannya berurutan, dan keterkaitannya bisa dimatikan per item (bab 4).

---

## 3. Peran pengguna dan hak akses

Satu akun bisa memiliki lebih dari satu peran. Bilah atas memuat **pengalih peran** (Coach, Fasilitator, CMS, dan Peserta untuk pratinjau).

| Peran | Kode | Bisa melakukan |
| --- | --- | --- |
| Superadmin | SUPERADMIN | Semua, termasuk penyedia kecerdasan buatan, kuota, lembaga, harga, pembayaran, akun media sosial, jejak audit |
| Admin lembaga | SCHOOL_ADMIN | Kelola kelas, pengajar, peserta di lembaganya; tagihan lembaga |
| Coach | COACH | Rancangan (Aktualisasi, Aksi Perubahan, Proyek Perubahan), sesi coaching, milestone, learning journal, lesson learned, latihan seminar, review |
| Fasilitator | FACILITATOR | Struktur kelas, semua aktivitas kelas, studi kasus, kuis, materi wajib, studio materi, kelas langsung |
| Pengelola konten | CMS_MANAGER | CMS: hubungkan akun media sosial, buat dan jadwalkan konten, terbitkan ke semua platform, statistik |
| Mentor | MENTOR | Lewat tautan khusus tanpa akun: membaca kartu progres peserta bimbingannya dan menulis di catatan tiga arah |
| Peserta | STUDENT | Mengerjakan aktivitas, rancangan, jurnal, melihat tanggapan dan umpan balik |

Coach Labs memakai kode TEACHER. Gho Studio memecahnya menjadi COACH dan FACILITATOR; satu pengguna boleh memiliki keduanya.

Login: email dan kata sandi, serta Google Sign-In. Peserta bergabung dengan **kode kelas** (contoh `LTS-12-4821`) atau tautan undangan. Admin dapat menempel daftar email peserta (allowlist).

---

## 4. Struktur kelas: Pelatihan › Agenda › Mata Pelatihan › Aktivitas

Urutan tingkat (dikonfirmasi pemilik produk): **Nama Pelatihan › Nama Agenda › Nama Mata Pelatihan › Aktivitas.**

Contoh:

```
Latsar CPNS Angkatan XII                    3 Nov – 20 Des
├─ Agenda 1 · Sikap Perilaku Bela Negara     3 – 7 Nov
│   ├─ Wawasan Kebangsaan                    3 – 4 Nov
│   │   ├─ 1. Video
│   │   └─ 2. Kuis
├─ Agenda 2 · Nilai-nilai Dasar PNS          10 – 21 Nov
│   ├─ Berorientasi Pelayanan                10 – 12 Nov
│   │   ├─ 1. Video (wajib ditonton sampai habis)
│   │   ├─ 2. Audiobook (wajib didengar sampai habis)
│   │   ├─ 3. eBook Heyzine (wajib dibaca semua halaman)
│   │   ├─ 4. Kuis 8 soal, 8 bentuk
│   │   └─ 5. Studi kasus interaktif
...
└─ Agenda 4 · Habituasi                      1 – 20 Des
    └─ Rancangan Aktualisasi (coach)
```

### 4.1 Jadwal

- Setiap tingkat (Pelatihan, Agenda, Mata Pelatihan, Aktivitas) punya **waktu buka** dan **waktu tutup** (tanggal dan jam, zona WIB/WITA/WIT mengikuti kelas).
- Jadwal tingkat bawah tidak boleh melewati tingkat di atasnya. Sistem memberi peringatan saat menyimpan.
- Di luar jadwal, item terlihat tetapi tidak bisa dikerjakan (status "Belum dibuka" atau "Sudah ditutup"). Coach atau fasilitator bisa memberi perpanjangan per peserta.

### 4.2 Berurutan dan keterkaitan

- **Bawaan: berurutan.** Jika dalam satu tingkat ada beberapa item (beberapa Agenda dalam satu Pelatihan, beberapa Mata Pelatihan dalam satu Agenda, beberapa Aktivitas dalam satu Mata Pelatihan), item pertama wajib selesai sebelum item berikutnya terbuka.
- **Dinamis.** Ada tombol geser "Berurutan" di setiap item. Jika dimatikan, item itu bisa dikerjakan kapan saja selama jadwalnya buka. Tombol juga ada di tingkat induk ("Agenda berurutan") untuk mengatur semua anaknya sekaligus.
- Opsional per item: prasyarat khusus (misalnya Studi kasus baru terbuka setelah Kuis lulus, bukan sekadar dikerjakan).

### 4.3 Definisi "selesai"

| Jenis | Selesai bila |
| --- | --- |
| Video, Audiobook | Diputar sampai ≥ 95% durasi dan mencapai akhir, tanpa melompat ke bagian yang belum diputar (bab 11) |
| eBook (Heyzine) | Semua halaman dibuka, atau waktu baca minimal tercapai dan halaman terakhir dicapai (bab 11) |
| Kuis | Nilai ≥ nilai lulus (atau cukup dikerjakan, sesuai pengaturan) |
| Aktivitas jawaban | Jawaban terkirim (atau disetujui coach/fasilitator, sesuai pengaturan) |
| Mata Pelatihan, Agenda, Pelatihan | Semua anak yang ditandai wajib sudah selesai |

### 4.4 Tampilan

- Fasilitator: layar **Struktur kelas** berupa pohon yang bisa dibuka-tutup, dengan chip jadwal dan tombol geser "Berurutan" di setiap baris, tombol "+ Tambah", dan seret untuk mengubah urutan.
- Peserta: jalur belajar dengan breadcrumb (`Latsar CPNS Angkatan XII › Agenda 2 › Berorientasi Pelayanan`), item terkunci diberi ikon gembok dan teks "Selesaikan materi sebelumnya dulu".

---

## 5. Navigasi dan peta layar

Desktop: sidebar kiri dengan kelompok menu. HP: navigasi bawah berisi 4 menu utama dan tombol **Lainnya** yang membuka lembar menu dari bawah. Bilah atas: logo, pengalih peran, indikator simpan, tema terang/gelap, avatar.

**Peran Coach**

| Kelompok | Menu | Rute prototipe |
| --- | --- | --- |
| Utama | Beranda | `coach-beranda` |
| Utama | Rancangan | `coach-rancangan` |
| Utama | Sesi coaching | `coach-sesi` |
| Utama | Review | `coach-review` |
| Pendampingan | Milestone | `coach-milestone` |
| Pendampingan | Learning journal | `coach-jurnal` |
| Pendampingan | Lesson learned | `coach-lapangan` |
| Pendampingan | Latihan seminar | `coach-seminar` |
| Kelola | Peserta | `coach-kelas` |

**Peran Fasilitator**

| Kelompok | Menu | Rute prototipe |
| --- | --- | --- |
| Utama | Beranda | `fas-beranda` |
| Utama | Struktur kelas | `fas-struktur` |
| Utama | Perancang | `fas-perancang` |
| Utama | Aktivitas | `fas-aktivitas` |
| Utama | Kelas langsung | `fas-live` |
| Perangkat | Studio materi | `fas-studio` |
| Perangkat | Kuis | `fas-kuis` |

**Peran CMS (Content Management System)**

| Kelompok | Menu | Rute prototipe |
| --- | --- | --- |
| Konten | Beranda (statistik per platform, jadwal minggu ini, ide konten dari kelas) | `cms-beranda` |
| Konten | Terbitkan (unggah sekali, caption per platform, status) | `cms-terbit` |
| Konten | Kalender konten | `cms-kalender` |
| Konten | Statistik | `cms-statistik` |
| Pengaturan | Akun terhubung | `cms-akun` |

**Peserta**: Tugas saya (`pes-beranda`), Materi wajib (`pes-materi`), Rancangan (`pes-rancangan`), Tugas tulis (`pes-tugas`), Studi kasus (`pes-kasus`), Kuis (`pes-kuis`), Jurnal (`pes-jurnal`, untuk PKP/PKA/PKN).

**Admin (dari Coach Labs)**: Konten, Lembaga, Akses, Harga, Pembayaran, Notifikasi, Penyedia kecerdasan buatan dan pemakaian, Akun media sosial, Jejak audit.

---

## 6. Katalog aktivitas

Setiap jenis punya: konfigurasi, tampilan peserta, aturan tanggapan otomatis, rubrik (bila dinilai), pengaturan **izin unggah dokumen** (bab 10), jadwal, dan pengaturan berurutan. Kolom "Pelatihan": L = Latsar, P = PKP, A = PKA, N = PKN Tk II.

### 6.1 Milik Coach

| Kode | Nama | Pelatihan | Isi |
| --- | --- | --- | --- |
| PLAN_ACTUALIZATION | Rancangan Aktualisasi | L | Sistematika bertahap, cek mutu tiap bagian, susun rancangan (bab 7) |
| PLAN_CHANGE_ACTION | Rancangan Aksi Perubahan | P, A | Idem |
| PLAN_CHANGE_PROJECT | Rancangan Proyek Perubahan | N | Idem |
| LEARNING_JOURNAL | Learning journal | P, A, N | Jurnal selama implementasi aksi/proyek perubahan (8.5) |
| LESSON_LEARNED_FIELD | Lesson learned studi lapangan | P, A | Lembar observasi, catatan temuan, kaitan dengan aksi perubahan (8.6) |
| LESSON_LEARNED | Lesson learned | N | Pelajaran yang dikaitkan dengan proyek perubahan (8.6) |

Ditambah fitur khas coach (bab 8): Sesi coaching GROW, Catatan tiga arah, Papan milestone dan peringatan dini, Kartu progres untuk mentor, Latihan seminar, Peta stakeholder interaktif, Mesin waktu dampak, Galeri praktik baik lintas angkatan.

### 6.2 Milik Fasilitator: aktivitas baru yang seru

| Kode | Nama | Pelatihan | Cara kerja |
| --- | --- | --- | --- |
| CRISIS_ROOM | Ruang krisis | LPAN | Krisis berjalan dengan jam hitung mundur; kabar baru masuk tiap beberapa menit; peserta memutuskan cepat; skor kecepatan dan ketepatan |
| ESCAPE_ROOM | Escape room regulasi | LPAN | Teka-teki berantai dari pasal, SOP, nilai dasar; kunci berikutnya terbuka bila jawaban tepat; bisa per kelompok |
| CITIZEN_HEARING | Sidang warga | LP | Warga simulasi dengan keluhan dan emosi berbeda; peserta menanggapi; dinilai empati, solusi, kepatuhan aturan |
| PRESS_CONFERENCE | Konferensi pers | PAN | Wartawan simulasi bertanya tajam; dinilai kejelasan, empati, konsistensi |
| PITCH_STAGE | Panggung pitching inovasi | PAN | Pitch tertulis atau lisan (teks) ke panel juri simulasi yang menilai dampak, biaya, risiko |
| MYSTERY_SHOPPER | Mystery shopper | LP | Peserta menilai layanan dari sudut warga dengan lembar penilaian, lalu menyusun rekomendasi |
| EMPATHY_MAP | Peta empati warga | LPA | Kolom pikir, rasa, kata, lakukan; sistem mencari celah dan kebutuhan tersembunyi |
| FIVE_WHY | 5 Why berantai | LPAN | Setiap jawaban "mengapa" disusul pertanyaan berikutnya sampai akar masalah |
| GALLERY_WALK | Gallery walk digital | LPAN | Karya kelompok dipajang; peserta memberi bintang dan komentar; rangkuman otomatis |
| IDEA_AUCTION | Lelang ide | LPAN | Peserta memegang koin virtual untuk "membeli" ide kelompok lain; papan peringkat |
| GUESS_DATA | Tebak data | LPAN | Peserta menebak angka kinerja, data sebenarnya dibuka, lalu dibahas |
| INTERVIEW_ROLEPLAY | Wawancara tokoh | PAN | Role-play dengan kepala daerah, warga, atau mentor untuk menggali isu |
| DILEMMA_CARDS | Kartu dilema kilat | LP | 10 dilema singkat dalam 5 menit; pola keputusan direkap |
| FUTURE_LETTER | Surat untuk diri setahun lagi | LPAN | Komitmen pribadi; dikirim kembali ke email peserta di akhir pelatihan |
| WRITING_TASK | Tugas tulis | LPAN | Editor lengkap dengan persamaan; unggah dokumen bisa diizinkan atau dimatikan |
| CASE_INTERACTIVE | Studi kasus interaktif | LPAN | Satu alur, kondisi berubah setiap jawaban (bab 13) |
| QUIZ | Kuis | LPAN | 10 bentuk soal (bab 12) |
| SEMINAR_SIM | Simulasi seminar | LPAN | Penguji simulasi bertanya satu per satu |
| STAKEHOLDER_LOBBY | Lobi stakeholder | PAN | Sistem berperan sebagai stakeholder yang menolak |
| PREMORTEM | Pre-mortem | PAN | Penyebab gagal menjadi daftar risiko dan mitigasi |
| ETHICS_DILEMMA | Dilema etik BerAKHLAK | LP | Keputusan dinilai terhadap perilaku BerAKHLAK resmi |
| FISHBONE | Fishbone interaktif | LPA | Peserta memetakan penyebab ke 6M; dikoreksi dan diberi akar prioritas |
| STAKEHOLDER_MAP | Peta stakeholder | PAN | Kuadran pengaruh × kepentingan dengan strategi per kuadran |
| ECON_CALC | Kalkulator nilai ekonomi | PAN | Manfaat dan Net Economic Value dihitung di browser; narasi otomatis |
| PITCH | Elevator pitch | LPAN | Skor kejelasan dan daya tarik, versi perbaikan |
| RP0_CHALLENGE | Tantangan inovasi Rp0 | L | Uji kelayakan kegiatan tanpa biaya |
| POLICY_DEBATE | Debat kebijakan | AN | Posisi berlawanan, lalu argumen dinilai |
| POLICY_BRIEF | Policy brief | AN | Policy brief 1 halaman dari rancangan |
| LEADERSHIP_REFLECTION | Refleksi kepemimpinan | PAN | Rencana pengembangan diri |

### 6.3 Milik Fasilitator: adopsi Coach Labs (semua)

Konfigurasi mengikuti Coach Labs (nilai bawaan dalam kurung), ditambah tanggapan otomatis.

| Kode | Nama | Konfigurasi Coach Labs | Tambahan di Gho Studio |
| --- | --- | --- | --- |
| READING | Materi bacaan | konten markdown | Peserta bertanya, jawaban hanya dari materi itu |
| EBOOK | eBook | url, note, aspectRatio | Embed Heyzine, wajib dibaca (bab 11), kuis cek pemahaman |
| VIDEO | Video | url, note, aspectRatio, checkpoints | Wajib ditonton sampai habis (bab 11), pertanyaan di checkpoint |
| AUDIO | Audiobook | url, note, aspectRatio | Wajib didengar sampai habis (bab 11) |
| QUIZ | Kuis | passingScore (70), maxAttempts, showAnswers, timeLimitMinutes | 10 bentuk soal (bab 12) |
| OPEN_QUESTION | Pertanyaan terbuka | prompt, hint, rubric, minChars (20), maxChars (2000) | Dinilai per rubrik; editor lengkap; unggah opsional |
| CASE_STUDY | Bedah kasus | narrative, questions[], solutionPrompt, guide, minChars, maxChars (3000) | Bisa diubah menjadi Studi kasus interaktif |
| DISCUSSION | Diskusi | topic, prompts[], rules, minReplies (1) | Pemantik, moderasi, rangkuman |
| REFLECTION | Refleksi | intro, questions[], minChars, maxChars | Tanggapan dan satu pertanyaan pendalaman |
| JOURNAL | Jurnal | guide, schedule, minEntries | Untuk kelas fasilitator; Learning journal coaching ada di 8.5 |
| SUMMARY | Ringkasan | instruction, minChars (50), maxChars (1500) | Dinilai kelengkapan dan ketepatan |
| DECISION_SIM | Simulasi keputusan | scenario, choices[{text, feedback}] | Skenario bercabang dari pilihan peserta |
| SELF_ASSESSMENT | Penilaian diri | intro, statements[], scaleLabels, results[] | Hasil ditafsirkan |
| ORDERING | Urutkan langkah | instruction, steps[], feedback | Alasan urutan dijelaskan |
| POLL | Polling | question, options[], showResults | Rangkuman untuk fasilitator |
| ACTION_PLAN | Rencana penerapan | instruction, minActions (1), maxActions (5) | Uji keterukuran |
| COMMITMENT | Komitmen | question, targetLabel | Komitmen ditajamkan |
| EVALUATION | Evaluasi | intro, aspects[], scaleLabels (1-7), suggestionPrompt | Rangkuman satu angkatan |
| PROJECT | Proyek | daftar periksa | Umpan balik per tahap |
| ZOOM | Kelas langsung (Zoom) | meetingNumber, passcode (tersembunyi), note | Kuis dan polling langsung |

Konten Coach Labs yang ada (3 jalur Pelatihan ASN Digital, 15 modul, 50 aktivitas, 35 kuis, 175 soal) harus bisa dimigrasikan. Pemetaan: Jalur (course) → Pelatihan, Modul → Mata Pelatihan (Agenda dibuat saat migrasi, bawaan satu agenda per jalur), Aktivitas tetap. `prerequisite`, `opensAt`, `closesAt` di Coach Labs dipetakan ke aturan berurutan dan jadwal (bab 4).

---

## 7. Rancangan bertahap: sistematika 4 pelatihan

Milik peran Coach. Sistematika disimpan sebagai **template** JSON yang bisa diedit tanpa mengubah kode: bagian, pertanyaan (teks, penjelasan, contoh, tipe isian, wajib, aturan cek mutu), dan struktur output.

### 7.1 Kolom identitas (Bagian I)

| Kolom | Latsar | PKP | PKA | PKN Tk II | Tipe |
| --- | --- | --- | --- | --- | --- |
| Nama lengkap dengan gelar | Ya | Ya | Ya | Ya | teks |
| NIP | | Ya | Ya | Ya | teks 18 digit |
| Jabatan | Ya | Ya | Ya | Ya | teks |
| Unit kerja | Ya | Ya | Ya | Ya | teks |
| Instansi | | Ya | Ya | (dalam unit kerja) | teks |
| Nama mentor | Ya | Ya | Ya | Ya | teks |
| Jabatan mentor | Ya | Ya | Ya | Ya | teks |
| Coach/pembimbing | Ya | Ya | Ya | Ya | pilihan, boleh isi sendiri |
| NIP coach | | Ya | Ya | Ya | otomatis |
| Nama pelatihan | | Ya | Ya | Ya | pilihan |
| Agenda pelatihan | | Ya | Ya | Ya | pilihan |
| Penyelenggara | | Ya | Ya | Ya | pilihan |
| Tanggal pengisian | Ya | Ya | Ya | Ya | tanggal |

Identitas diisi sekali per peserta per kelas dan dipakai ulang.

### 7.2 Bagian per pelatihan

| Pelatihan | Bagian (setelah identitas) | Pertanyaan strategis |
| --- | --- | --- |
| Latsar | II Tugas utama · III Permasalahan (Negasi-Fokus-Lokus) · IV Data pendukung · V Dugaan penyebab · VI Target terukur | 5 |
| PKP | II Kenali Kinerja (3) · III Temukan Solusi (5) · IV Rancang Aksi (3) · V Ukur Nilai Perubahan (2) · VI Pimpin Perubahan (1) | 14 |
| PKA | Sama dengan PKP, konteks Pejabat Administrator dan kinerja organisasi | 14 |
| PKN Tk II | II Tupoksi dan IKU · III Isu Strategis · IV Peran Strategis · V Akar Masalah · VI Arah Perubahan · VII Kepemimpinan dan Pengembangan Diri (masing-masing 5) | 30 |

Teks lengkap setiap pertanyaan dan contoh jawaban ada di **Lampiran A**.

### 7.3 Cek mutu per bagian

Tombol **Cek mutu** di setiap bagian mengembalikan status per kriteria (lulus/belum) dan saran singkat. Peserta tetap boleh lanjut; status terlihat oleh coach.

| Bagian | Kriteria |
| --- | --- |
| Latsar III Permasalahan | Ada negasi (masih/belum/kurang/rendah/lambat/sering); fokus spesifik; lokus jelas; satu kalimat; tidak menyebut solusi; satu masalah |
| Latsar IV Data pendukung | Minimal 1 data kuantitatif atau kualitatif; ada sumber; bernomor |
| Latsar V Dugaan penyebab | 3 sampai 6 poin; bukan solusi; dapat dipetakan ke 6M |
| Latsar VI Target | Ada kondisi awal, angka target, batas waktu 1 bulan; tidak menambah indikator baru |
| PKP/PKA II.3 Masalah prioritas | Kondisi aktual, kondisi seharusnya, kesenjangan, evidence, dalam kewenangan |
| PKP/PKA V.2 Nilai ekonomi | Ada biaya implementasi; kondisi sebelum dan sesudah dalam angka dan satuan |
| PKN III Isu strategis | Spesifik, berbasis data, ada kesenjangan, lokasi, pihak terdampak |

Jika data pendukung kosong (Latsar IV), penyusunan dihentikan dan peserta diminta melengkapi.

### 7.4 Susun rancangan

Tombol **Susun rancangan** menyusun rancangan per bagian output (Lampiran B), satu bagian per panggilan agar tidak terpotong dan bisa diulang per bagian. Setiap hasil disimpan sebagai versi. Coach mereview per bagian, berkomentar, menyetujui, atau mengembalikan. Peserta mengunduh Word dengan format resmi.

Aturan isi dari form asli:
- Berbasis data peserta, tanpa opini tambahan.
- Latsar: 5 kegiatan kreatif, out of the box, Rp0, berdampak langsung, tidak saling bergantung; tiap kegiatan minimal 5 tahap dengan output konkret; relevansi Agenda 2 (BerAKHLAK) memakai perwujudan perilaku resmi; relevansi Agenda 3 (Smart ASN dan Manajemen ASN) operasional; lima alternatif judul akronim.
- PKP/PKA/PKN: Fishbone, SWOT dan strategi, terobosan inovatif (bukan sekadar aplikasi), milestone jangka pendek/menengah/panjang, manajemen risiko, pemetaan stakeholder (Promoters, Latents, Defenders, Apathetics), relevansi Asta Cita, Indonesia Emas 2045, SDGs, RB Berdampak, nilai ekonomi dan Net Economic Value, strategi marketing, pemetaan sikap dan perilaku kepemimpinan.

---

## 8. Fitur peran Coach

### 8.1 Beranda coach
Statistik (peserta coaching, rancangan menunggu review, sesi coaching minggu ini, laporan lesson learned), daftar kelas dengan progres, rangkuman kelas (pola jawaban satu angkatan), kartu **Lingkup coaching** (6 aktivitas di 6.1), kartu **Fitur khas coach**, daftar "Perlu perhatian".

### 8.2 Rancangan dan review
Tab per pelatihan (Latsar · Aktualisasi, PKP/PKA · Aksi Perubahan, PKN · Proyek Perubahan). Tabel peserta: judul akronim, titik progres per bagian, hasil cek mutu, status. Tombol Review membuka layar review: jawaban, skor rubrik otomatis, draf umpan balik yang bisa diedit, **Setujui dan kirim**, **Tulis ulang** (lebih menyemangati / lebih tegas / lebih singkat), **Kembalikan untuk revisi**. Pengaturan per kelas: tanggapan otomatis langsung tampil, atau ditahan sampai disetujui coach.

Alat di dalam rancangan:
- **Peta stakeholder interaktif**: peserta menempatkan stakeholder pada kuadran pengaruh × kepentingan (Kelola dekat, Puaskan, Beri informasi, Pantau); sistem menyarankan strategi per kuadran dan langsung mengisi bagian stakeholder rancangan.
- **Mesin waktu dampak**: dari indikator dan milestone, sistem memproyeksikan kondisi 1 tahun ke depan dengan dan tanpa inovasi (misalnya 14 hari → 5 hari proses, ≈ 2.300 jam kerja warga hemat per tahun). Dipakai untuk bagian dampak dan nilai ekonomi.

### 8.3 Sesi coaching (model GROW)
- Pilih peserta, lalu catat sesi per tahap: **Goal** (tujuan), **Reality** (kenyataan), **Options** (pilihan), **Will** (komitmen dan tenggat).
- Tombol **Sarankan pertanyaan kuat**: 3 pertanyaan coaching sesuai tahap dan isi rancangan peserta.
- Simpan per tahap; di tahap terakhir **Selesai, kirim ringkasan** ke peserta dan mentor.
- **Jadwal coaching**: coach membuka slot, peserta memilih sendiri (daring/tatap muka), pengingat otomatis.
- **Kontrak coaching**: kesepakatan sesi pertama (frekuensi, durasi, kewajiban jurnal), disetujui peserta dan mentor.
- **Riwayat sesi** bernomor dengan fokus tiap sesi.

### 8.4 Catatan tiga arah dan kartu progres mentor
- **Catatan tiga arah**: satu utas per peserta untuk coach, mentor, dan peserta. Mentor masuk lewat tautan bertoken tanpa akun (bisa dicabut).
- **Kartu progres untuk mentor**: satu halaman ringkas (judul, progres milestone, indikator awal → terkini → target), diperbarui otomatis, dibagikan lewat tautan atau WhatsApp.

### 8.5 Learning journal (PKP, PKA, PKN Tk II)
- Peserta menulis jurnal selama implementasi aksi atau proyek perubahan: kegiatan dan pengalaman, insight atau pelajaran kepemimpinan, hambatan, capaian indikator (angka).
- Tanggapan otomatis: kemajuan terhadap target (persen jalan), saran mengatasi hambatan, kaitan dengan perilaku kepemimpinan.
- Grafik kemajuan indikator dari awal ke target.
- Coach: daftar jurnal dengan penanda (sesuai milestone / perlu pendampingan / berisiko mundur), tombol beri komentar, **Rangkum** jurnal satu kelas per minggu.
- Latsar tidak memakai learning journal di peran coach.

### 8.6 Lesson learned
- **PKP dan PKA: Lesson learned studi lapangan.** Coach mengisi lokasi dan fokus pengamatan, sistem menyusun **lembar observasi** (5 pertanyaan: inovasi yang dilihat, alur, siapa penggeraknya, bukti dampak, apa yang diadaptasi ke milestone aksi perubahan). Peserta menulis temuan sebagai teks. Sistem menganalisis dan mengaitkan pelajaran dengan aksi perubahan masing-masing.
- **PKN Tk II: Lesson learned.** Struktur sama, tanpa lokasi studi lapangan wajib; pelajaran dikaitkan dengan proyek perubahan.
- Status laporan: belum kirim, terkirim, sudah dianalisis, perlu review.
- **Galeri praktik baik lintas angkatan**: lesson learned terbaik (atas izin peserta) disimpan sebagai bank inspirasi untuk angkatan berikutnya.

### 8.7 Papan milestone dan peringatan dini
- Papan kanban per kelas: Belum mulai, Berjalan, Tertunda, Selesai. Kartu: milestone, peserta, tenggat atau status.
- **Radar peringatan dini**: menandai peserta dengan milestone lewat tenggat, jurnal kosong ≥ 5 hari, atau indikator tidak bergerak 2 minggu.
- Tombol **Siapkan sapaan**: draf pesan yang hangat dan spesifik untuk peserta tersebut, bisa diedit lalu dikirim.
- Tab Latsar menampilkan kegiatan aktualisasi.

### 8.8 Latihan seminar
- Peserta berlatih menghadapi penguji simulasi sebelum seminar rancangan atau laporan.
- Pilihan penguji: Penguji akademik, Mentor/atasan, Pengguna layanan, Pejabat anggaran. Masing-masing 3 sampai 5 pertanyaan berdasarkan rancangan peserta.
- Setiap jawaban disimpan, diberi skor dan catatan penguji.
- **Skor kesiapan** per peserta: penguasaan data, argumentasi, kepemimpinan, kejelasan, ketepatan waktu.
- Coach melihat **kesiapan kelas** (Siap, Hampir siap, Belum latihan).

### 8.9 Peserta (kelola)
Tabel peserta per kelas: progres rancangan, jumlah entri jurnal, status lesson learned, status. Kode kelas, unduh rekap nilai (Excel di browser), ekspor semua rancangan ke Word (zip di browser).

---

## 9. Fitur peran Fasilitator

### 9.1 Beranda fasilitator
Statistik, akses cepat (Perancang, Studi kasus interaktif, Struktur kelas, Bank aktivitas, Susun modul, Bahan tayang, Naskah audiobook, Kuis, Polling dan word cloud), jadwal sesi.

### 9.2 Struktur kelas
Lihat bab 4.

### 9.3 Perancang
Input: jenis aktivitas, kelas, tujuan dan konteks, jumlah putaran/soal, gaya tanggapan ke peserta (menyemangati tetap kritis / tegas dan ringkas / sokratik), **cara peserta menjawab** (izinkan unggah dokumen: ya/tidak; editor lengkap dengan persamaan: ya/tidak). Output berupa draf yang bisa diedit: judul, instruksi, situasi/materi, soal, rubrik berbobot, aturan main, batas karakter. Tombol: Terbitkan ke kelas, Rancang ulang, Simpan sebagai template.

### 9.4 Bank aktivitas
Kartu semua aktivitas fasilitator (6.2 dan 6.3) dengan filter Semua, Baru, Dari Coach Labs, dan filter pelatihan.

### 9.5 Studio materi
Jenis: Modul, Bahan tayang, eBook (naskah), Naskah audiobook, Skenario sesi. Input: mata pelatihan, tujuan pembelajaran, durasi (JP). Output tersimpan di bank materi, bisa diedit, dan diekspor (Word, PowerPoint di browser). Pratinjau suara audiobook memakai Web Speech API di perangkat.

### 9.6 Kelas langsung
Kode sesi (contoh `GHO-482`), peserta ikut dari HP. Aktivitas: polling, word cloud, **kuis langsung berwaktu** dengan papan peringkat (12.10), diskusi dengan pertanyaan pemantik, studi kasus dengan rilis kondisi serentak, exit ticket. Tombol **Bacaan kelas** merangkum hasil. Pembaruan memakai polling ringan 2 sampai 3 detik atau Server-Sent Events dengan data agregat kecil.

### 9.7 Kuis, materi wajib, studi kasus
Lihat bab 11, 12, 13.

---

## 10. Editor jawaban dan unggah dokumen

### 10.1 Editor lengkap seperti Word
Dipakai di semua jawaban panjang (Tugas tulis, Pertanyaan terbuka, Refleksi, Ringkasan, bagian rancangan, jurnal, lesson learned, dan lainnya).

Bilah alat:
- Urungkan, Ulangi
- Gaya paragraf (Normal, Judul 1, Judul 2, Kutipan), ukuran huruf
- Tebal, Miring, Garis bawah, Coret, Subskrip, Superskrip
- Warna huruf, Stabilo
- Rata kiri, tengah, kanan, kiri-kanan
- Daftar berpoin, daftar bernomor, indentasi
- Tabel (tambah/hapus baris dan kolom), Tautan, Garis pemisah, Simbol (Σ √ π ± × ÷ ≤ ≥ ≠ ≈ ∞ α β Δ → ° ½ ² ³ dan lainnya), Hapus format
- **∑ Persamaan**: editor persamaan seperti di Word, dengan templat (pecahan, akar, pangkat, indeks, jumlah, integral, matriks, persentase), **papan ketik matematika di layar** (otomatis muncul di HP), pratinjau, dan klik persamaan untuk mengedit lagi.
- Hitungan kata, status simpan otomatis.
- Pintasan keyboard standar (Ctrl+B, Ctrl+I, Ctrl+U, Ctrl+Z, dan lainnya).

Teknologi yang disarankan: **TipTap** (berbasis ProseMirror) untuk editor, **MathLive** untuk input persamaan dan papan ketik matematika, **KaTeX** untuk menampilkan persamaan di halaman baca. Persamaan disimpan sebagai LaTeX di dalam dokumen JSON editor. Ekspor Word mengubah persamaan menjadi OMML (persamaan Word asli) atau gambar SVG sebagai cadangan.

Penyimpanan: konten editor disimpan sebagai JSON TipTap plus teks polos (untuk penilaian otomatis dan pencarian). Batas karakter dihitung dari teks polos.

### 10.2 Ketik langsung atau unggah dokumen
- Setiap aktivitas jawaban punya pengaturan **Izinkan unggah dokumen** (tombol geser) di Perancang dan di pengaturan aktivitas.
- **Aktif**: peserta melihat dua tab, "Ketik langsung" dan "Unggah dokumen". Format PDF, DOC, DOCX (bisa diatur), ukuran maksimal bawaan 5 MB (bisa diatur), satu file per jawaban (bisa diatur).
- **Mati**: tab unggah tidak muncul. Peserta wajib mengetik di editor. Label "Wajib ketik langsung".
- Penilaian otomatis untuk file: teks diekstrak dari PDF/DOCX di proses latar (worker), lalu dinilai seperti teks ketikan. Jika ekstraksi gagal, coach/fasilitator menilai manual.
- Agar server ringan: browser mengunggah **langsung ke penyimpanan objek** (S3-compatible, misalnya Cloudflare R2, Wasabi, atau MinIO) memakai URL bertanda tangan (presigned URL) dari server. Server aplikasi hanya menyimpan metadata (nama, ukuran, jenis, kunci objek). File dipindai antivirus (opsional ClamAV di worker) dan dihapus otomatis sesuai kebijakan retensi (bawaan: 1 tahun setelah kelas selesai).

---

## 11. Materi wajib: video, audio, eBook Heyzine

Materi wajib harus **ditonton, didengar, atau dibaca sampai selesai** sebelum aktivitas berikutnya terbuka (bila pengaturan berurutan aktif, bab 4).

### 11.1 Video
- Sumber: YouTube (IFrame Player API) atau video di server/penyimpanan objek (elemen HTML5 `<video>`).
- Lacak `maxWatched` (detik terjauh yang sudah ditonton). Jika peserta melompat melewati `maxWatched + 2 detik`, kembalikan ke `maxWatched` dan tampilkan "Belum bisa melompat ke bagian yang belum ditonton". Mundur selalu boleh.
- Kecepatan putar maksimal bisa diatur (bawaan 1,5×).
- Opsional: jeda otomatis saat tab tidak aktif (`visibilitychange`).
- Selesai bila `maxWatched ≥ 95%` durasi dan event `ended` terjadi. Kirim kemajuan ke server tiap 10 detik dan saat jeda/selesai (tabel `media_progress`), sehingga bisa dilanjutkan di perangkat lain.
- Checkpoint (dari Coach Labs): pertanyaan muncul di detik tertentu; video berhenti sampai dijawab.

### 11.2 Audio (audiobook)
Sama dengan video memakai elemen `<audio>`; tampilan pemutar sederhana dengan sampul, putar/jeda, mundur 10 detik, dan bilah kemajuan yang tidak bisa diseret melewati `maxWatched`.

### 11.3 eBook Heyzine
- eBook dibuat dan dihosting di **Heyzine** oleh fasilitator. Di Gho Studio cukup menempel tautan flipbook; ditampilkan dengan `iframe` (rasio bisa diatur).
- Pelacakan halaman: jika paket Heyzine yang dipakai menyediakan event pergantian halaman (melalui API JavaScript atau `postMessage` dari embed), catat setiap halaman yang dibuka. **Programmer wajib memeriksa dokumentasi Heyzine terbaru** untuk fitur ini.
- Cadangan bila event halaman tidak tersedia: fasilitator mengisi jumlah halaman; sistem menghitung waktu baca minimal (bawaan 20 detik × jumlah halaman); tombol "Saya sudah selesai membaca" baru aktif setelah waktu minimal tercapai saat tab aktif; diikuti 2 sampai 3 soal cek pemahaman.
- Selesai bila semua halaman dibuka, atau waktu minimal + halaman terakhir + soal cek pemahaman terjawab.

### 11.4 Tampilan peserta
Jalur materi berurutan (Video → Audiobook → eBook → Kuis) dengan status Selesai, Kerjakan, Terkunci, breadcrumb struktur kelas, dan keterangan aturan di bawah pemutar. Lihat layar `pes-materi` di prototipe.

---

## 12. Kuis dalam 10 bentuk

Fasilitator memilih satu atau beberapa bentuk (boleh campur dalam satu kuis). Soal dibuat otomatis dari materi lalu bisa diedit. Pengaturan: jumlah soal, tingkat kesulitan (campuran C2–C5, mudah, sulit/HOTS), nilai lulus, waktu, jumlah percobaan, acak soal dan opsi, tampilkan pembahasan.

| No | Bentuk | Kode | Data soal | Penilaian |
| --- | --- | --- | --- | --- |
| 12.1 | Pilihan ganda biasa | MCQ | prompt, options[4–5], correctId | Benar/salah |
| 12.2 | Pilihan ganda kompleks | MCQ_MULTI | prompt, options[], correctIds[] | Bawaan: semua harus tepat; opsi skor parsial |
| 12.3 | Benar/salah per pernyataan | TF_MULTI | prompt, statements[{text, isTrue}] | Bawaan: semua tepat; opsi skor parsial |
| 12.4 | Menjodohkan | MATCHING | left[], right[], pairs[] | Per pasangan atau semua |
| 12.5 | Mengurutkan | ORDERING | items[], correctOrder[] | Urutan persis atau skor parsial |
| 12.6 | Isian singkat | SHORT_ANSWER | prompt dengan ____, accepted[] (tidak peka huruf besar, toleransi spasi) | Cocok dengan salah satu kunci |
| 12.7 | Berbasis gambar | IMAGE | gambar (diagram, grafik, foto) + soal pilihan | Benar/salah |
| 12.8 | Pilihan ganda beralasan | TWO_TIER | prompt, options[], correctId, reasons[], correctReasonId | Benar jika jawaban dan alasan tepat |
| 12.9 | Skala sikap situasional | LIKERT_SJT | situasi, pernyataan sikap, skala 1–5, bobot ideal | Skor kedekatan dengan sikap ideal BerAKHLAK/kepemimpinan |
| 12.10 | Kuis langsung berwaktu | LIVE | soal pilihan dengan batas detik | Poin = benar × bonus kecepatan; papan peringkat langsung |

Gambar soal (12.7): diunggah fasilitator ke penyimpanan objek atau berupa SVG/diagram yang dibuat sistem. Setiap jawaban salah mendapat **Penjelasan** personal. Format soal kompatibel dengan kuis Coach Labs (MCQ) agar migrasi mulus.

---

## 13. Studi kasus interaktif

Milik peran Fasilitator. Berjalan dalam **satu alur percakapan**, bukan dua tahap terpisah. Peserta menjawab, lalu sistem langsung memunculkan **perubahan kondisi masyarakat atau situasi** sebagai akibat dari jawaban itu, dan peserta merespons lagi, sampai batas putaran.

### 13.1 Alur
1. Kasus dibuka: dari template, atau dibuat dari konteks peserta (jabatan, unit kerja, isu) sehingga setiap peserta mendapat kasus berbeda tetapi setara.
2. Peserta menulis keputusan dan alasan (editor; unggah sesuai pengaturan).
3. **Server menyimpan jawaban** (`case_turns`, role = participant). Browser menampilkan "Tersimpan HH.MM".
4. Server meminta tanggapan dengan seluruh riwayat sesi. Hasil JSON: tanggapan atas jawaban + perubahan kondisi berikutnya (atau penilaian akhir).
5. Server menyimpan tanggapan sebagai baris baru.
6. Diulang sampai putaran habis. Penilaian akhir: skor per rubrik, total, pola keputusan, area pengembangan. Status `menunggu_validasi`, lalu divalidasi fasilitator.

Jika langkah 4 gagal, jawaban sudah aman; tampilkan "Minta tanggapan lagi". Peserta bisa menutup browser dan melanjutkan.

### 13.2 Pengaturan
Jumlah putaran (1–10, bawaan 3); tingkat kesulitan; jenis perubahan kondisi (SDM berkurang, anggaran dipotong, regulasi baru, gangguan sistem, isu viral, bencana, mutasi pimpinan, kelompok rentan terdampak, konflik antarunit); mode rilis (otomatis, atau serentak oleh fasilitator di kelas langsung); rubrik bawaan (analisis 25%, adaptasi 30%, BerAKHLAK atau kepemimpinan 25%, dampak layanan 20%); gaya tanggapan.

---

## 14. CMS: Content Management System (media sosial)

CMS adalah area ketiga Gho Studio, dengan peran dan menu sendiri (sejajar Coach dan Fasilitator). Tujuan: pemilik akun **mengunggah sekali di Gho Studio**, lalu konten terbit sekaligus di YouTube, Facebook, Instagram, TikTok, dan platform lain yang dipilih.

### 14.1 Batasan penting
Unggahan yang dibuat **langsung di aplikasi Facebook (atau TikTok, dan sebagainya) tidak bisa diteruskan otomatis** ke platform lain, karena platform tidak membagikan unggahan ke platform pesaing dan tidak menyediakan pemicu untuk itu. Karena itu **Gho Studio menjadi satu-satunya tempat mengunggah**. Hal ini dijelaskan di layar dalam kotak "Catatan penting".

### 14.2 Alur
1. Hubungkan akun sekali (OAuth) di **Kelola akun**: YouTube, Facebook Page, Instagram (akun Business/Creator yang tersambung ke Facebook Page), TikTok, X, LinkedIn, Threads, Saluran WhatsApp (bila API tersedia).
2. Pilih video atau foto. File diunggah **langsung dari browser ke penyimpanan objek** (presigned URL, mendukung unggahan bertahap/resumable untuk video besar), tidak melewati server aplikasi.
3. Tulis caption utama.
4. Centang platform tujuan. Sistem memberi peringatan bila format tidak cocok (misalnya YouTube dan TikTok butuh video; Instagram butuh foto atau video dengan rasio tertentu).
5. Tombol **Sesuaikan caption tiap platform**: caption otomatis disesuaikan per platform (YouTube: judul, deskripsi, penanda waktu, tagar; Instagram: maks. 30 tagar; TikTok: judul pendek; X: maks. 280 karakter; LinkedIn: gaya profesional). Semua bisa diedit di tab per platform.
6. Waktu terbit: Sekarang, Jadwalkan (tanggal dan jam), atau Waktu terbaik tiap platform. Visibilitas YouTube (publik, tidak publik, pribadi).
7. **Terbitkan**: worker di server mengirim ke setiap platform secara paralel. Status per platform: Menunggu, Mengunggah, Terbit (dengan tautan), Gagal (dengan alasan dan tombol Coba lagi).
8. Riwayat dan **Statistik**: daftar unggahan dengan platform, tautan, tayangan, suka, komentar yang ditarik berkala, plus bacaan statistik (platform terkuat, waktu terbaik).
9. **Kalender konten**: tampilan mingguan dan bulanan semua jadwal terbit; seret untuk memindah jadwal.
10. **Ide konten dari kelas**: CMS menyarankan konten dari kegiatan kelas (hasil polling, lesson learned terbaik atas izin peserta, soal kuis tersulit). Data peserta tidak pernah diterbitkan tanpa persetujuan.

### 14.3 Pilihan teknis
- **Opsi A, cepat (disarankan untuk tahap awal):** layanan agregator posting multiplatform yang punya API (contoh: Ayrshare, atau layanan sejenis). Satu API untuk banyak platform, persetujuan aplikasi ke tiap platform sudah ditangani penyedia. Biaya berlangganan bulanan.
- **Opsi B, langsung ke API resmi:** YouTube Data API v3 (unggah video, ada kuota harian), Meta Graph API untuk Facebook Page dan Instagram Content Publishing, TikTok Content Posting API (aplikasi harus lolos audit TikTok sebelum bisa memposting publik), X API (berbayar), LinkedIn API, Threads API. Lebih murah jangka panjang, tetapi butuh pendaftaran dan review aplikasi di setiap platform.
- Programmer wajib memeriksa **ketentuan, kuota, dan biaya terbaru** setiap platform sebelum memilih.
- Token OAuth disimpan terenkripsi di server, diperbarui otomatis, dan bisa dicabut.

---

## 15. Fitur peserta

- Tugas saya: kartu tugas dengan sumber ("Dari coach" atau "Dari fasilitator"), progres, tenggat; umpan balik terbaru.
- Jalur belajar sesuai struktur kelas, dengan gembok untuk item yang belum terbuka.
- Materi wajib (bab 11), Rancangan (bab 7), Tugas tulis dengan editor lengkap (bab 10), Studi kasus (bab 13), Kuis 10 bentuk (bab 12), Learning journal (PKP/PKA/PKN), Lesson learned, Latihan seminar.
- Tanggapan langsung dan umpan balik coach/fasilitator yang sudah disetujui.
- Riwayat versi; unduh rancangan final ke Word.
- Notifikasi: umpan balik baru, revisi diminta, tugas baru, item baru terbuka, tenggat, jadwal coaching.

---

## 16. Kecerdasan buatan di balik layar

### 16.1 Penyedia
Lapisan penyedia mengikuti Coach Labs: tabel penyedia dengan `kind` (OPENAI, OPENAI_COMPATIBLE), label, model, baseUrl, kunci terenkripsi (tampil 4 karakter terakhir), enabled, isDefault, maxOutputTokens, extraBody. Penyedia aktif saat ini: **DeepSeek** (OPENAI_COMPATIBLE). Penyedia lain bisa ditambah tanpa mengubah kode. Fitur tertentu boleh memakai model berbeda (model cepat untuk tanggapan studi kasus dan kuis langsung, model kuat untuk penyusunan rancangan).

### 16.2 Fungsi

| Fungsi | Dipicu oleh | Output (JSON) |
| --- | --- | --- |
| design_assignment | Perancang | {title, instructions, material, items[], rubric[], rules} |
| check_section | Cek mutu rancangan | {criteria[{name, pass, note}], suggestion} |
| compose_plan_section | Susun rancangan | {section_key, content_markdown} |
| case_open / case_turn | Studi kasus | {feedback, condition{category, impact, text, question}} atau {final{scores[], total, pattern, development}} |
| grade_open | Jawaban terbuka, refleksi, tugas tulis, file yang diekstrak | {scores[{criterion, score, reason}], total, feedback} |
| rewrite_feedback | Tulis ulang | {feedback} |
| generate_quiz | Kuis | {questions[{type, ...sesuai bentuk 12.x, explanation}]} |
| explain_wrong | Jawaban kuis salah | {explanation} |
| roleplay_turn | Simulasi seminar, latihan seminar, lobi, debat, sidang warga, konferensi pers, wawancara tokoh, panggung pitching | {reply, score?, note?} |
| crisis_event | Ruang krisis | {headline, detail, deadline_seconds} |
| coaching_questions | Sesi coaching GROW | {questions[3]} |
| nudge_message | Radar peringatan dini | {message} |
| journal_progress | Learning journal | {progress_pct, response, risk_flag} |
| observation_sheet | Lesson learned studi lapangan | {questions[]} |
| lesson_analysis | Lesson learned | {lessons[], adaptation, linked_milestone} |
| impact_projection | Mesin waktu dampak | {with_innovation, without_innovation, annual_benefit, narrative} |
| social_captions | Media sosial | {captions{yt{title, description, tags[]}, fb, ig, tt, x, li, th, wa}} |
| generate_material | Studio materi | {type, title, outline[], content_markdown, slides?[]} |
| class_summary | Rangkuman kelas, bacaan kelas langsung, rangkuman jurnal | {summary, patterns[], suggestions[]} |

Semua fungsi memakai output JSON terstruktur dan divalidasi skema di server; bila tidak valid, ulangi sekali lalu tampilkan galat yang ramah.

### 16.3 Contoh prompt sistem

**case_turn**
```
Kamu adalah fasilitator studi kasus untuk peserta {pelatihan} ({sapaan}).
Konteks peserta: jabatan {jabatan}, unit kerja {unit}.
Kasus: {situasi_awal}
Aturan: {jumlah_putaran} putaran; sekarang putaran {n}. Jenis perubahan kondisi: {daftar}. Kesulitan: {kesulitan}. Gaya: {gaya}.
Riwayat: {semua_putaran_sebelumnya}
Jawaban peserta terbaru: {jawaban}
Tugas:
1) Tanggapi jawaban dalam 2-4 kalimat: kekuatan, satu kelemahan, kaitan dengan nilai {BerAKHLAK|kepemimpinan}. Kutip sebagian kecil jawaban.
2) Jika n < {jumlah_putaran}: buat perubahan kondisi realistis di pemerintahan Indonesia, akibat logis dari jawaban atau faktor luar, lalu ajukan satu pertanyaan lanjutan.
3) Jika n = {jumlah_putaran}: penilaian akhir per rubrik {rubrik} (0-100), total berbobot, pola keputusan, area pengembangan.
Jangan mengarang angka di luar kasus. Jangan menyebut dirimu AI. Jawab hanya JSON sesuai skema.
```

**coaching_questions**
```
Kamu membantu coach widyaiswara. Tahap GROW: {tahap}. Ringkasan rancangan peserta: {ringkasan}. Catatan sesi: {catatan}.
Buat 3 pertanyaan coaching yang terbuka, singkat, tidak menggurui, dan tidak memberi solusi. Sapa peserta dengan "{sapaan}". JSON {questions:[...]}.
```

**social_captions**
```
Sesuaikan caption utama berikut untuk setiap platform: {caption_utama}. Konteks: widyaiswara, pelatihan ASN, bahasa Indonesia yang hangat dan profesional.
Aturan: YouTube {judul ≤ 100 karakter, deskripsi, tagar}; Instagram {≤ 30 tagar}; TikTok {judul pendek, tagar di akhir}; X {≤ 280 karakter}; LinkedIn {gaya profesional}.
Jangan membuat klaim yang tidak ada di caption utama. JSON {captions:{...}}.
```

**check_section (Latsar Bagian III)** dan **grade_open**: seperti versi 1 (kriteria Negasi-Fokus-Lokus; nilai per rubrik dengan alasan 1 kalimat dan umpan balik 3-5 kalimat).

### 16.4 Kuota, biaya, keamanan
Kuota per peserta per hari dan per kelas; catat pemakaian per penyedia, model, fungsi, token, biaya perkiraan (`ai_usage`); batas laju per pengguna; saring masukan; instruksi di dalam jawaban peserta diperlakukan sebagai data (prompt injection); hasil di-cache.

---

## 17. Struktur database

PostgreSQL. Semua id UUID; semua tabel punya `created_at`, `updated_at`. Tabel jawaban append-only.

| Tabel | Kolom utama |
| --- | --- |
| users | id, email, name, password_hash, google_id, roles[], nip, school_id |
| schools | id, name, billing |
| trainings (pelatihan) | id, class_id, name, type (LATSAR/PKP/PKA/PKN2), opens_at, closes_at, sequential_children |
| agendas | id, training_id, name, order, opens_at, closes_at, sequential, sequential_children |
| subjects (mata pelatihan) | id, agenda_id, name, order, opens_at, closes_at, sequential, sequential_children |
| activities | id, subject_id (nullable), class_id, owner_role (COACH/FACILITATOR), type, title, description, order, required, sequential, prerequisite_id, prerequisite_rule, opens_at, closes_at, points, allow_upload, upload_rules_json, editor_math, config_json, ai_config_json, rubric_json, status |
| completions | id, user_id, item_type (training/agenda/subject/activity), item_id, completed_at, source |
| schedule_overrides | id, user_id, item_type, item_id, opens_at, closes_at, granted_by |
| classes | id, school_id, training_type, name, batch, join_code, coach_ids[], facilitator_ids[], settings_json |
| enrollments | id, class_id, user_id, role |
| plan_templates | id, training, version, sections_json, output_json |
| participant_profiles | id, user_id, class_id, identity_json |
| answers | id, user_id, activity_id, item_key, value_text, value_doc_json (TipTap), file_id (nullable), version, client_saved_at |
| files | id, owner_id, bucket, object_key, name, mime, size, sha256, scan_status, extracted_text, expires_at |
| ai_checks | id, answer_id, function, result_json, model |
| plans / plan_sections | seperti versi 1 |
| media_items | id, activity_id, kind (video/audio/ebook), provider (youtube/server/heyzine), url, duration_sec, pages, min_seconds_per_page |
| media_progress | id, user_id, media_item_id, max_position_sec, pages_seen[], active_seconds, completed_at |
| quizzes | id, activity_id, passing_score, max_attempts, show_answers, time_limit_minutes, shuffle |
| quiz_questions | id, quiz_id, order, type (MCQ/MCQ_MULTI/TF_MULTI/MATCHING/ORDERING/SHORT_ANSWER/IMAGE/TWO_TIER/LIKERT_SJT/LIVE), prompt, data_json, answer_json, explanation, image_file_id, time_limit_sec |
| quiz_attempts | id, quiz_id, user_id, question_order[], answers_json, score |
| case_sessions / case_turns | seperti versi 1 |
| submissions | id, activity_id, user_id, answer_id, status, ai_scores_json, ai_feedback, coach_feedback, coach_score, reviewed_by |
| coaching_sessions | id, coach_id, user_id, number, scheduled_at, mode, grow_json {G,R,O,W}, summary, sent_at |
| coaching_slots | id, coach_id, starts_at, duration_min, mode, booked_by |
| coaching_contracts | id, coach_id, user_id, terms_json, accepted_by_user_at, accepted_by_mentor_at |
| three_way_notes | id, user_id (peserta), author_role (coach/mentor/peserta), author_name, body |
| mentor_links | id, user_id, mentor_name, mentor_position, token_hash, revoked_at |
| milestones | id, plan_id, user_id, title, term (pendek/menengah/panjang), due_at, status (belum/berjalan/tertunda/selesai), indicator_value |
| early_warnings | id, user_id, rule, detail, resolved_at |
| journal_entries | id, user_id, class_id, date, experience_doc, insight_doc, obstacle_doc, indicator_value, ai_response |
| field_observations | id, class_id, location, focus, sheet_json |
| lesson_learned | id, user_id, observation_id (nullable), body_doc, ai_analysis_json, status, gallery_opt_in |
| seminar_rehearsals | id, user_id, examiner_type, turns_json, scores_json |
| materials | id, owner_id, type, subject, title, content_md, slides_json |
| live_sessions / live_responses | seperti versi 1, ditambah live_quiz_scores |
| social_accounts | id, owner_id, platform, account_name, token_encrypted, refresh_encrypted, expires_at, status |
| social_posts | id, owner_id, media_file_id, caption_master, captions_json, platforms[], scheduled_at, status |
| social_post_targets | id, post_id, platform, status (menunggu/mengunggah/terbit/gagal), remote_id, remote_url, error, stats_json |
| ai_providers / ai_usage / ai_quota / notifications / audit_logs / prices, orders, payments | seperti versi 1 dan Coach Labs |

Indeks penting: `answers(user_id, activity_id, item_key, version)`, `case_turns(session_id, round)`, `completions(user_id, item_type, item_id)`, `media_progress(user_id, media_item_id)`, `social_post_targets(post_id, platform)`.

---

## 18. API

REST JSON dengan cookie sesi httpOnly dan token CSRF (seperti Coach Labs). Endpoint tambahan dari versi 1 (yang lama tetap berlaku: answers, plans, cases, quizzes, review, submissions, live, admin Coach Labs):

| Metode | Path | Keterangan |
| --- | --- | --- |
| GET | /api/classes/:id/structure | Pohon Pelatihan › Agenda › Mata Pelatihan › Aktivitas dengan jadwal, status kunci per peserta |
| PATCH | /api/structure/:type/:id | Ubah nama, urutan, jadwal, sequential |
| POST | /api/structure/:type/:id/override | Perpanjangan jadwal per peserta |
| POST | /api/files/presign | URL unggah bertanda tangan (cek allow_upload, jenis, ukuran) |
| POST | /api/files/:id/complete | Konfirmasi unggah selesai; antre pindai dan ekstraksi teks |
| PUT | /api/media/:id/progress | Kemajuan video/audio/eBook {position, pages_seen, active_seconds} |
| POST | /api/coaching/sessions | Buat sesi GROW; PATCH per tahap |
| POST | /api/coaching/sessions/:id/questions | Saran pertanyaan kuat |
| GET/POST | /api/coaching/slots | Slot jadwal coaching |
| GET/POST | /api/notes/:userId | Catatan tiga arah |
| GET | /m/:token | Halaman mentor (kartu progres + catatan tiga arah), tanpa login |
| GET/PATCH | /api/milestones | Papan milestone |
| GET | /api/early-warnings?class= | Radar peringatan dini |
| POST | /api/early-warnings/:id/nudge | Draf sapaan |
| POST | /api/journal | Entri learning journal (simpan dulu), lalu POST /api/journal/:id/response |
| POST | /api/lessons/observation-sheet | Lembar observasi |
| POST | /api/lessons | Lesson learned (simpan dulu), lalu POST /api/lessons/:id/analyze |
| POST | /api/seminar | Mulai latihan seminar; POST /api/seminar/:id/answer |
| POST | /api/plans/:id/impact | Mesin waktu dampak |
| GET/POST | /api/social/accounts | Hubungkan akun (OAuth callback per platform) |
| POST | /api/social/media/presign | Unggah media (resumable) |
| POST | /api/social/captions | Sesuaikan caption per platform |
| POST | /api/social/posts | Terbitkan atau jadwalkan |
| GET | /api/social/posts/:id | Status per platform |
| POST | /api/social/posts/:id/retry/:platform | Coba lagi satu platform |

Pemisahan simpan dan tanggapan (dua panggilan) tetap menjadi inti aturan wajib nomor 2.

---

## 19. Arsitektur dan teknologi

- **Frontend**: Vue 3 + Vite + TypeScript, Tailwind CSS, Pinia, PWA ringan (cache aset, antrean simpan offline). Font Plus Jakarta Sans dan JetBrains Mono. Editor TipTap, MathLive, KaTeX. YouTube IFrame Player API.
- **Backend**: Node.js (Fastify atau Express) + Prisma. **Worker latar** (BullMQ + Redis, atau antrean berbasis tabel) untuk: penyusunan rancangan, ekstraksi teks file, pemindaian file, penerbitan media sosial, penarikan statistik, pengingat dan peringatan dini (cron).
- **Database**: PostgreSQL.
- **Penyimpanan objek**: S3-compatible (Cloudflare R2, Wasabi, MinIO di VPS lain). Unggahan langsung dari browser dengan presigned URL.
- **Kecerdasan buatan**: modul penyedia OpenAI-compatible (DeepSeek bawaan).
- **Media sosial**: agregator API (opsi A) atau API resmi per platform (opsi B), bab 14.
- **Ekspor di browser**: `docx`, `pptxgenjs`, `SheetJS`, `JSZip`.
- **Hosting**: VPS gho-elearning.com dengan Nginx dan HTTPS, contoh domain `studio.gho-elearning.com`.

Alasan server tetap ringan: file tidak lewat server aplikasi, dokumen dibuat di browser, kecerdasan buatan diproses penyedia dan di-cache, polling kelas langsung mengirim data agregat kecil, pekerjaan berat di worker.

Desain visual mengikuti prototipe: hijau tua (#0f5c4d) warna utama, kuning kunyit (#d98b16) aksen, biru (#2f6fd6) untuk elemen hasil otomatis, mode terang dan gelap, sudut membulat 12 px, kartu dengan bayangan lembut.

---

## 20. Kebutuhan non-fungsional

- Muat halaman pertama di bawah 2 detik pada 4G.
- Simpan otomatis: tiba di server dalam 3 detik; tidak ada kehilangan data saat refresh, tutup browser, atau koneksi putus.
- Tanggapan studi kasus tampil bertahap (streaming); respon pertama di bawah 5 detik.
- 500 peserta aktif bersamaan pada VPS 2 vCPU / 4 GB.
- Kemudahan: setiap tugas utama tercapai maksimal 3 ketukan dari beranda; navigasi HP maksimal 4 menu + Lainnya; semua tombol berlabel jelas dalam bahasa Indonesia.
- Aksesibilitas: kontras cukup, navigasi keyboard, label pada semua kolom, mendukung `prefers-reduced-motion`.
- Keamanan: kata sandi di-hash, cookie httpOnly + CSRF, otorisasi per peran di setiap endpoint, kunci API dan token OAuth terenkripsi, tautan mentor bertoken dan bisa dicabut, jejak audit.
- Privasi: data peserta hanya terlihat peserta, coach/fasilitator kelasnya, mentornya (terbatas), dan admin.
- Cadangan database harian.

---

## 21. Tahapan pembangunan dan kriteria selesai

**Tahap 1, inti**
- Akun, peran, pengalih peran, kelas dan kode kelas.
- Struktur kelas 4 tingkat dengan jadwal dan aturan berurutan yang bisa dimatikan.
- Simpan otomatis dengan cadangan offline.
- Editor lengkap dengan persamaan; izin unggah per aktivitas.
- Rancangan bertahap 4 pelatihan, cek mutu, susun rancangan, ekspor Word.
- Materi wajib (video, audio, eBook Heyzine) dengan pelacakan selesai.
- Kuis 10 bentuk.
- Studi kasus interaktif.
- Review dengan draf tanggapan.
- Penyedia kecerdasan buatan dan kuota.

Kriteria selesai tahap 1:
1. Peserta mengisi studi kasus 3 putaran, menutup browser di tengah putaran 2, membuka lagi, dan semua jawaban serta kondisi masih ada.
2. Saat kecerdasan buatan gagal, jawaban tetap tersimpan dan bisa diminta tanggapan ulang.
3. Rancangan Latsar lengkap terunduh sebagai .docx dengan output sesuai Lampiran B.
4. Video tidak bisa dilompati ke depan; aktivitas berikutnya baru terbuka setelah video selesai; mematikan "Berurutan" langsung membuka item itu.
5. Saat izin unggah dimatikan, tab unggah tidak muncul dan API presign menolak permintaan.
6. Persamaan yang diketik di editor tampil benar dan tetap utuh saat diekspor ke Word.
7. Tidak ada kata "AI" di menu, judul, tombol, dan nama aktivitas.

**Tahap 2, coach**: sesi coaching GROW, jadwal dan kontrak coaching, catatan tiga arah dan halaman mentor, papan milestone dan peringatan dini, learning journal, lesson learned, latihan seminar, peta stakeholder interaktif, mesin waktu dampak, galeri praktik baik.

**Tahap 3, fasilitator**: aktivitas baru 6.2, studio materi, kelas langsung dengan kuis berwaktu, bank materi, migrasi konten Coach Labs.

**Tahap 4, CMS**: hubungkan akun, unggah sekali, caption per platform, kalender konten, status per platform, statistik, ide konten dari kelas.

---

## 22. Lampiran A: daftar pertanyaan sistematika


#### Latsar CPNS

**Bagian I: Identitas / Informasi Umum**

Lihat daftar kolom identitas pada bagian 7.1.

**Bagian II : Tugas Utama yang Berkaitan dengan Permasalahan**

- (Paragraf) Adik-Adik CPNS, silakan kamu tuliskan Tugas Utama yang paling berkaitan langsung dengan permasalahan yang akan diselesaikan (maksimal 3 poin, sesuai jabatan) ?
  - Contoh: Tugas utama saya, sebagai berikut : 1) Melakukan input dan pembaruan data layanan sesuai SOP, 2) Menyiapkan dan memverifikasi kelengkapan berkas layanan sesuai ketentuan, dan 3) Memberikan informasi alur layanan kepada pengguna sesuai kewenangan.

**Bagian III : Permasalahan (Negasi–Fokus–Lokus)**

- (Jawaban Singkat) Adik-Adik CPNS, silakan kamu tuliskan Permasalahan dalam 1 kalimat dengan format Negasi– Fokus–Lokus agar masalahnya spesifik dan mudah dianalisis ?
  - Contoh: Permasalahan saya adalah masih lambatnya proses verifikasi berkas layanan pada Subbidang Pelayanan Administrasi di Unit Kerja X Kabupaten Y

**Bagian IV : Data Pendukung Permasalahan.**

- (Paragraf) Adik-Adik CPNS, silakan kamu tuliskan Data Pendukung Permasalahan yang memuat minimal 1 data kuantitatif atau 1 data kualitatif (boleh keduanya), dan tuliskan sumbernya singkat ?
  - Contoh: Data pendukung saya, sebagai berikut : 1) Data kuantitatif: rata-rata waktu verifikasi berkas 25 menit per pemohon berdasarkan rekap layanan minggu ke-1 Februari 2026, 2) Data kualitatif: terdapat keluhan pemohon tentang lamanya antrean yang dicatat pada buku pengaduan tanggal 10 Februari 2026.

**Bagian V : Dugaan Penyebab Utama.**

- (Paragraf) Adik-Adik CPNS, silakan kamu tuliskan Dugaan Penyebab Utama yang paling mungkin menyebabkan permasalahan tersebut (3 sampai 6 poin), tulis singkat, spesifik, dan sesuai kondisi unit kerja ?
  - Contoh: Dugaan penyebab utama saya, sebagai berikut : 1) Berkas pemohon sering tidak lengkap sehingga verifikasi berulang, 2) Tidak ada urutan ceklist verifikasi yang konsisten antar petugas, 3) Informasi persyaratan kurang jelas sehingga pemohon datang tanpa persiapan, 4) Sarana pemindaian/print terbatas saat jam ramai, dan 5) Tidak ada target waktu verifikasi yang dipahami bersama.

**Bagian VI : Target Perbaikan Terukur.**

- (Paragraf) Adik-Adik CPNS, silakan kamu tuliskan Target Perbaikan Terukur dalam 1 kalimat angka yang realistis dicapai dalam 1 bulan, selaras dengan permasalahan dan data pendukung ?
  - Contoh: Target perbaikan terukur saya adalah menurunkan rata-rata waktu verifikasi berkas dari 25 menit menjadi 15 menit per pemohon dalam 1 bulan.


#### PKP

**Bagian I: Identitas / Informasi Umum**

Lihat daftar kolom identitas pada bagian 7.1.

**Bagian II: Kenali Kinerja**

- (Paragraf) Bapak/Ibu, berdasarkan jabatan yang telah disampaikan pada Bagian Informasi Umum, silakan jelaskan tugas pokok dan fungsi Bapak/Ibu yang paling berkaitan dengan area pengendalian pekerjaan yang akan menjadi fokus Aksi Perubahan. Cantumkan regulasi resmi yang menjadi dasar tugas pokok dan fungsi tersebut.
  - Contoh: Saya menjabat sebagai Kepala Seksi Pelayanan Kesehatan Primer pada Dinas Kesehatan Kabupaten Arunika dengan tugas mengendalikan pelaksanaan pelayanan kesehatan primer, termasuk pembinaan, pemantauan, dan evaluasi pelayanan pada Puskesmas. Fungsi yang menjadi fokus Aksi Perubahan adalah pengendalian tindak lanjut hasil pemantauan pelayanan kesehatan primer, dengan kewenangan membagi tugas kepada pe
- (Paragraf) Bapak/Ibu, berdasarkan tugas pokok dan fungsi yang telah dijelaskan, silakan uraikan sasaran kinerja atau hasil kerja utama yang paling berkaitan dengan fokus Aksi Perubahan. Jelaskan indikator kinerja, target atau standar yang harus dicapai, kondisi atau capaian aktual saat ini, serta data atau evidence pendukung apabila tersedia.
  - Contoh: Sasaran kinerja yang menjadi tanggung jawab saya adalah meningkatnya ketepatan tindak lanjut hasil pemantauan pelayanan kesehatan primer pada Puskesmas di Kabupaten Arunika. Indikator yang digunakan adalah persentase hasil pemantauan pelayanan kesehatan primer yang ditindaklanjuti sesuai standar waktu, dengan target Tahun 2026 sebesar 95% ditindaklanjuti paling lambat 2 hari kerja setelah hasil pe
- (Paragraf) Bapak/Ibu, berdasarkan sasaran kinerja, target, dan kondisi aktual yang telah dijelaskan, silakan tentukan satu masalah pengendalian pekerjaan yang paling prioritas untuk diperbaiki melalui Aksi Perubahan. Jelaskan kondisi yang terjadi saat ini, kondisi yang seharusnya dicapai, kesenjangan yang terjadi, serta data atau evidence yang menunjukkan adanya masalah tersebut apabila tersedia.
  - Contoh: Masalah pengendalian pekerjaan yang menjadi prioritas adalah belum optimalnya ketepatan waktu tindak lanjut hasil pemantauan pelayanan kesehatan primer pada Puskesmas di Kabupaten Arunika. Kondisi Semester I Tahun 2026 menunjukkan baru 74% hasil pemantauan ditindaklanjuti sesuai standar waktu, sedangkan target yang ditetapkan sebesar 95% dengan waktu penyelesaian paling lambat 2 hari kerja, sehing

**Bagian III: Temukan Solusi**

- (Paragraf) Bapak/Ibu, berdasarkan masalah prioritas yang telah ditetapkan pada Bagian Kenali Kinerja, silakan jelaskan berbagai penyebab yang menurut pengalaman Bapak/Ibu membuat masalah tersebut terjadi atau terus berulang. Uraikan penyebab yang berkaitan dengan pelaksana pekerjaan, metode atau prosedur kerja, sarana pendukung, data atau informasi, koordinasi, serta aspek lain yang relevan dengan kondisi di unit kerja.
  - Contoh: Keterlambatan tindak lanjut hasil pemantauan pelayanan kesehatan primer terjadi karena pembagian tugas antarpetugas belum konsisten, hasil pemantauan dari Puskesmas masih disampaikan melalui beberapa media dan format yang berbeda, serta pemantauan terhadap batas waktu penyelesaian belum dilakukan secara rutin. Rekapitulasi dan verifikasi data masih dilakukan secara manual sehingga petugas membutuh
- (Paragraf) Bapak/Ibu, dari berbagai penyebab yang telah disampaikan pada Pertanyaan 1, apa saja kondisi di dalam organisasi yang menurut Bapak/Ibu sudah mendukung penyelesaian masalah dan apa saja yang masih menjadi kendala?
  - Contoh: Kondisi internal yang sudah mendukung adalah adanya dukungan Kepala Bidang Pelayanan Kesehatan, petugas yang memahami proses pemantauan pelayanan kesehatan primer, tersedianya komputer dan jaringan internet, serta SOP pemantauan dan tindak lanjut sebagai pedoman kerja. Kondisi yang masih menjadi kendala adalah pembagian tugas antarpetugas belum konsisten, format data dari Puskesmas belum seragam, 
- (Paragraf) Bapak/Ibu, selain kondisi internal unit kerja, silakan jelaskan kondisi eksternal yang dapat mendukung maupun menghambat penyelesaian masalah dan pelaksanaan Aksi Perubahan. Uraikan peluang yang dapat dimanfaatkan serta tantangan atau ancaman dari luar unit kerja yang perlu diantisipasi.
  - Contoh: Peluang yang dapat dimanfaatkan adalah komitmen Puskesmas untuk meningkatkan kualitas pelayanan kesehatan primer, kebijakan transformasi digital kesehatan, serta tersedianya teknologi yang dapat digunakan untuk mempercepat penyampaian dan pemantauan data tindak lanjut. Kebutuhan Puskesmas terhadap mekanisme pemantauan yang lebih sederhana dan cepat juga menjadi dukungan bagi perubahan. Tantangan y
- (Paragraf) Bapak/Ibu, berdasarkan masalah, penyebab, serta kondisi internal dan eksternal yang telah dijelaskan, silakan uraikan perubahan atau cara kerja baru yang menurut Bapak/Ibu paling dibutuhkan untuk memperbaiki pengendalian pekerjaan tersebut. Jelaskan apa yang perlu diubah dari kondisi saat ini dan hasil yang ingin dicapai melalui perubahan tersebut.
  - Contoh: Perubahan yang dibutuhkan adalah mengubah pengendalian tindak lanjut hasil pemantauan pelayanan kesehatan primer yang saat ini masih menggunakan beberapa media dan format menjadi satu mekanisme kerja yang lebih sederhana, seragam, dan mudah dipantau. Setiap hasil pemantauan akan memiliki penanggung jawab, batas waktu maksimal 2 hari kerja, status penyelesaian, serta mekanisme pengingat dan pemanta
- (Paragraf) Bapak/Ibu, berdasarkan perubahan atau cara kerja baru yang telah diusulkan, silakan jelaskan apa yang membuat gagasan tersebut lebih baik dibandingkan cara kerja saat ini. Uraikan nilai tambah, unsur kebaruan, kemudahan penerapan, serta manfaat yang diharapkan bagi pengendalian pekerjaan, kinerja unit, atau kualitas pelayanan.
  - Contoh: Gagasan ini lebih baik karena pengendalian tindak lanjut tidak lagi hanya mengandalkan penyampaian data melalui beberapa media dan komunikasi personal, tetapi menggunakan satu alur kerja yang memastikan setiap hasil pemantauan memiliki penanggung jawab, batas waktu maksimal 2 hari kerja, status penyelesaian, dan pengingat tindak lanjut. Nilai tambahnya adalah pekerjaan dapat dipantau lebih cepat, 

**Bagian IV: Rancang Aksi**

- (Paragraf) Bapak/Ibu, berdasarkan gagasan perubahan yang telah dirumuskan, silakan jelaskan pihak internal dan eksternal yang perlu dilibatkan agar Aksi Perubahan dapat dilaksanakan. Uraikan peran atau dukungan yang diharapkan dari masing-masing pihak serta tentukan pihak yang menurut Bapak/Ibu paling menentukan keberhasilan perubahan.
  - Contoh: Stakeholder internal yang perlu dilibatkan adalah Kepala Bidang Pelayanan Kesehatan sebagai Mentor dan pemberi arahan, Kepala Seksi Pelayanan Kesehatan Primer sebagai pengendali Aksi Perubahan, serta staf seksi sebagai pelaksana pemantauan dan verifikasi tindak lanjut. Stakeholder eksternal meliputi Kepala Puskesmas sebagai penanggung jawab pelaksanaan di masing-masing Puskesmas dan petugas penang
- (Paragraf) Bapak/Ibu, untuk melaksanakan gagasan perubahan tersebut, silakan jelaskan sumber daya yang sudah tersedia dan sumber daya tambahan yang masih dibutuhkan. Uraikan sumber daya manusia, sarana dan prasarana, anggaran, data atau informasi, teknologi, serta dukungan organisasi yang dapat digunakan untuk melaksanakan Aksi Perubahan.
  - Contoh: Sumber daya yang sudah tersedia meliputi staf Seksi Pelayanan Kesehatan Primer, petugas penanggung jawab program di Puskesmas, komputer dan jaringan internet, serta data hasil pemantauan dan rekapitulasi tindak lanjut pelayanan kesehatan primer. Pelaksanaan perubahan akan memanfaatkan sarana digital yang sudah tersedia sehingga tidak memerlukan pengadaan aplikasi baru. Sumber daya yang masih perlu
- (Paragraf) Bapak/Ibu, berdasarkan gagasan perubahan, stakeholder, dan sumber daya yang telah dijelaskan, silakan uraikan tahapan utama yang perlu dilakukan untuk melaksanakan Aksi Perubahan. Jelaskan kegiatan yang akan dilakukan, waktu pelaksanaan, hasil yang ingin dicapai, pihak yang terlibat, evidence yang akan dihasilkan, serta kendala atau risiko yang mungkin muncul selama pelaksanaan.
  - Contoh: Tahap awal dilaksanakan pada minggu pertama melalui konsultasi dengan Mentor, pembentukan tim, pemetaan proses tindak lanjut saat ini, dan penyusunan mekanisme pengendalian dengan batas waktu maksimal 2 hari kerja. Minggu kedua sampai keempat dilakukan penyusunan format pemantauan, pembagian peran, sosialisasi, dan uji coba bersama Puskesmas, kemudian minggu kelima sampai kedelapan dilakukan pener

**Bagian V: Ukur Nilai Perubahan**

- (Paragraf) Bapak/Ibu, berdasarkan Aksi Perubahan yang akan dilaksanakan, silakan jelaskan manfaat atau perubahan kinerja yang diharapkan setelah Aksi Perubahan diterapkan. Uraikan kontribusinya terhadap peningkatan pengendalian pekerjaan, kualitas hasil kerja atau pelayanan, serta prioritas pembangunan atau tata kelola pemerintahan yang relevan.
  - Contoh: Aksi Perubahan diharapkan meningkatkan ketepatan waktu tindak lanjut hasil pemantauan pelayanan kesehatan primer dari capaian Semester I Tahun 2026 sebesar 74% menjadi sekurang- kurangnya 95% sesuai standar maksimal 2 hari kerja. Perubahan ini juga diharapkan memperjelas penanggung jawab setiap tindak lanjut, mempercepat pemantauan status pekerjaan, mengurangi keterlambatan, serta membantu Puskesm
- (Paragraf) Bapak/Ibu, berdasarkan manfaat Aksi Perubahan yang telah dijelaskan, silakan sampaikan data yang dapat digunakan untuk menghitung nilai ekonomi perubahan. Jelaskan biaya yang diperlukan untuk melaksanakan Aksi Perubahan serta manfaat ekonomi yang dapat diukur, seperti penghematan waktu kerja, pengurangan biaya operasional, peningkatan produktivitas, pengurangan pekerjaan berulang, avoided cost, atau peningkatan penerimaan apabila relevan.
  - Contoh: Pelaksanaan Aksi Perubahan memanfaatkan SDM, komputer, jaringan internet, dan sarana digital yang sudah tersedia sehingga tidak memerlukan pengadaan aplikasi baru. Biaya tambahan diperkirakan sebesar Rp2.000.000 untuk sosialisasi, bahan pendukung, dan pelaksanaan uji coba. Sebelum perubahan, proses pencatatan, verifikasi, koordinasi, dan pemantauan satu tindak lanjut membutuhkan rata-rata 40 menit

**Bagian VI: Pimpin Perubahan**

- (Paragraf) Bapak/Ibu, sebagai Pejabat Pengawas yang akan memimpin pelaksanaan Aksi Perubahan, silakan jelaskan cara Bapak/Ibu membangun dukungan agar perubahan dapat diterima, dilaksanakan, dan dipertahankan oleh tim serta stakeholder terkait. Jelaskan pula strategi komunikasi, cara memperkenalkan Aksi Perubahan, kemampuan kepemimpinan yang perlu diperkuat, serta komitmen Bapak/Ibu untuk menjaga keberlanjutan perubahan setelah masa aktualisasi berakhir.
  - Contoh: Saya akan membangun dukungan dengan melibatkan tim Seksi Pelayanan Kesehatan Primer dan Puskesmas sejak tahap persiapan, menjelaskan manfaat perubahan, membagi peran secara jelas, serta mengevaluasi pelaksanaannya secara berkala. Komunikasi dilakukan melalui konsultasi dengan Mentor, rapat internal, koordinasi dengan Kepala Puskesmas dan petugas penanggung jawab, serta penyampaian perkembangan cap


#### PKA

**Bagian I: Identitas / Informasi Umum**

Lihat daftar kolom identitas pada bagian 7.1.

**Bagian II: Kenali Kinerja**

- (Paragraf) Bapak/Ibu, berdasarkan jabatan yang telah disampaikan pada Bagian Informasi Umum, silakan jelaskan tugas pokok dan fungsi Bapak/Ibu yang paling berkaitan dengan area kinerja yang akan menjadi fokus Aksi Perubahan. Cantumkan regulasi resmi yang menjadi dasar tugas pokok dan fungsi tersebut.
  - Contoh: Saya menjabat sebagai Kepala Bidang Pelayanan Kesehatan pada Dinas Kesehatan Kabupaten Arunika dengan tugas mengoordinasikan dan mengendalikan penyelenggaraan pelayanan kesehatan primer dan rujukan. Fungsi yang paling berkaitan dengan Aksi Perubahan adalah pembinaan, pemantauan, evaluasi, dan pengendalian tindak lanjut kinerja pelayanan pada fasilitas pelayanan kesehatan, dengan kewenangan mengoor
- (Paragraf) Bapak/Ibu, berdasarkan tugas pokok dan fungsi yang telah dijelaskan pada Pertanyaan 1, silakan tuliskan sasaran kinerja dan indikator kinerja yang paling berkaitan dengan fokus Aksi Perubahan Bapak/Ibu. Cantumkan target dan capaian aktual berdasarkan dokumen kinerja resmi terbaru.
  - Contoh: Sasaran kinerja yang terkait dengan fokus Aksi Perubahan adalah meningkatnya kualitas pelayanan kesehatan pada fasilitas pelayanan kesehatan di Kabupaten Arunika. Indikator kinerjanya adalah persentase fasilitas pelayanan kesehatan yang memenuhi standar mutu pelayanan, dengan target Tahun 2026 sebesar 95% dan capaian aktual Semester I Tahun 2026 sebesar 72%. Data tersebut bersumber dari Perjanjian
- (Paragraf) Bapak/Ibu, berdasarkan sasaran dan indikator kinerja pada Pertanyaan 2, silakan jelaskan satu masalah kinerja prioritas yang paling perlu diselesaikan melalui Aksi Perubahan. Tunjukkan kondisi aktual, kondisi yang seharusnya dicapai, serta data atau evidence yang membuktikan adanya masalah tersebut.
  - Contoh: Masalah kinerja prioritas adalah belum optimalnya pemenuhan standar mutu pelayanan pada fasilitas pelayanan kesehatan di Kabupaten Arunika. Dari target Tahun 2026 sebesar 95%, capaian aktual Semester I Tahun 2026 baru mencapai 72%, sehingga masih terdapat gap kinerja sebesar 23 poin persentase. Kondisi ini menunjukkan masih terdapat fasilitas pelayanan kesehatan yang belum memenuhi standar mutu pe

**Bagian III: Temukan Solusi**

- (Paragraf) Bapak/Ibu, berdasarkan masalah kinerja prioritas yang telah ditetapkan pada Bagian Kenali Kinerja, menurut pengalaman Bapak/Ibu apa saja yang menyebabkan masalah tersebut terjadi?
  - Contoh: Belum optimalnya pemenuhan standar mutu pelayanan pada fasilitas pelayanan kesehatan di Kabupaten Arunika disebabkan oleh beberapa kondisi yang ditemukan dalam pelaksanaan pekerjaan, yaitu hasil pemantauan mutu dari fasilitas pelayanan kesehatan belum disampaikan dalam format yang seragam, kelengkapan data masih berbeda antar fasilitas, proses rekapitulasi dan verifikasi masih dilakukan melalui fi
- (Paragraf) Bapak/Ibu, dari berbagai penyebab yang telah disampaikan pada Pertanyaan 1, apa saja kondisi di dalam organisasi yang menurut Bapak/Ibu sudah mendukung penyelesaian masalah dan apa saja yang masih menjadi kendala?
  - Contoh: Kondisi internal yang sudah mendukung adalah adanya komitmen Kepala Dinas untuk meningkatkan mutu pelayanan, tersedianya tim pengelola mutu pada fasilitas pelayanan kesehatan, serta perangkat komputer dan jaringan internet yang dapat digunakan untuk mendukung pemantauan. Kondisi yang masih menjadi kendala adalah format pelaporan mutu belum seragam, data masih dikirim melalui file terpisah, proses 
- (Paragraf) Bapak/Ibu, selain kondisi di dalam organisasi, kondisi atau pihak dari luar organisasi apa saja yang menurut Bapak/Ibu dapat membantu dan apa saja yang dapat menghambat penyelesaian masalah kinerja tersebut?
  - Contoh: Kondisi eksternal yang dapat membantu adalah adanya kebijakan Kementerian Kesehatan yang mendorong peningkatan mutu pelayanan, dukungan fasilitas pelayanan kesehatan untuk melakukan perbaikan, serta semakin tersedianya teknologi digital yang dapat dimanfaatkan untuk mempercepat pemantauan dan penyampaian data mutu. Kondisi yang dapat menghambat adalah kedisiplinan penyampaian data antar fasilitas 
- (Paragraf) Bapak/Ibu, berdasarkan masalah dan berbagai kondisi yang telah disampaikan sebelumnya, perubahan atau cara baru apa yang menurut Bapak/Ibu paling dibutuhkan untuk memperbaiki kondisi tersebut dan meningkatkan kinerja?
  - Contoh: Saya ingin mengubah proses pemantauan mutu pelayanan kesehatan yang saat ini masih menggunakan format dan file terpisah menjadi mekanisme pemantauan yang lebih terintegrasi, sederhana, dan menggunakan format data yang seragam. Fasilitas pelayanan kesehatan dapat menyampaikan data mutu secara lebih tertib, sedangkan Bidang Pelayanan Kesehatan dapat melakukan verifikasi, memantau capaian, dan menind
- (Paragraf) Bapak/Ibu, dari perubahan atau cara baru yang telah disampaikan pada Pertanyaan 4, menurut Bapak/Ibu apa yang membuat gagasan tersebut berbeda atau lebih baik dibandingkan cara yang selama ini digunakan?
  - Contoh: Gagasan ini berbeda karena pemantauan mutu yang sebelumnya menggunakan berbagai format dan file terpisah akan dilakukan melalui satu mekanisme dengan format data yang seragam, sehingga capaian setiap fasilitas pelayanan kesehatan dapat dipantau dan ditindaklanjuti lebih cepat. Proses rekapitulasi dan verifikasi yang sebelumnya membutuhkan sekitar 4 hari kerja ditargetkan menjadi maksimal 1 hari ke

**Bagian IV: Rancang Aksi**

- (Paragraf) Bapak/Ibu, siapa saja pihak di dalam dan di luar organisasi yang menurut Bapak/Ibu perlu dilibatkan agar Aksi Perubahan dapat dilaksanakan dan mencapai hasil yang diharapkan?
  - Contoh: Pihak internal yang perlu dilibatkan adalah Kepala Dinas Kesehatan sebagai pemberi dukungan kebijakan dan pengarah, Sekretaris Dinas untuk mendukung koordinasi dan sumber daya, tim Bidang Pelayanan Kesehatan sebagai pelaksana, serta pengelola mutu pada fasilitas pelayanan kesehatan sebagai pengguna sekaligus penyedia data. Pihak eksternal yang relevan meliputi rumah sakit, klinik, dan fasilitas pe
- (Paragraf) Bapak/Ibu, agar perubahan yang direncanakan dapat benar-benar dilaksanakan, sumber daya apa saja yang sudah tersedia dan sumber daya apa yang masih perlu disiapkan atau diperkuat?
  - Contoh: Sumber daya yang sudah tersedia meliputi tim Bidang Pelayanan Kesehatan sebanyak 12 orang, pengelola mutu pada fasilitas pelayanan kesehatan, komputer dan jaringan internet, serta data hasil pemantauan mutu pelayanan Semester I Tahun 2026. Pada tahap awal belum tersedia anggaran khusus untuk pengembangan sistem baru, sehingga perubahan akan memanfaatkan sarana dan platform digital yang telah terse
- (Paragraf) Bapak/Ibu, agar Aksi Perubahan dapat dilaksanakan secara terarah, tahapan kegiatan apa saja yang menurut Bapak/Ibu perlu dilakukan, hasil apa yang ingin dicapai, dan kendala apa yang mungkin muncul selama pelaksanaannya?
  - Contoh: Tahapan yang akan dilakukan adalah membentuk tim, menyepakati mekanisme kerja dan format data mutu, menyiapkan media pemantauan, melakukan uji coba, memberikan pendampingan kepada pengelola mutu, melaksanakan mekanisme baru, serta melakukan monitoring dan evaluasi. Dalam 2 bulan pertama ditargetkan mekanisme pemantauan sudah diuji coba dan digunakan, waktu rekapitulasi dan verifikasi data yang saa

**Bagian V: Ukur Nilai Perubahan**

- (Paragraf) Bapak/Ibu, bagaimana Aksi Perubahan yang direncanakan dapat memberikan kontribusi terhadap prioritas pembangunan dan perbaikan tata kelola pemerintahan?
  - Contoh: Aksi Perubahan ini diharapkan mempercepat pemantauan mutu pelayanan kesehatan, meningkatkan ketepatan dan kelengkapan data, serta membantu Dinas Kesehatan dan fasilitas pelayanan kesehatan mengetahui indikator mutu yang belum tercapai untuk segera ditindaklanjuti. Waktu rekapitulasi dan verifikasi data yang saat ini sekitar 4 hari kerja ditargetkan menjadi maksimal 1 hari kerja, sedangkan capaian 
- (Paragraf) Bapak/Ibu, manfaat nyata apa yang diperkirakan akan diperoleh setelah Aksi Perubahan dilaksanakan, terutama dalam penghematan waktu, biaya, peningkatan produktivitas, pencegahan biaya, atau peningkatan penerimaan apabila relevan?
  - Contoh: Saat ini proses rekapitulasi dan verifikasi data mutu membutuhkan rata-rata 4 hari kerja setiap periode dengan melibatkan 4 pegawai, sekitar 3 jam per orang per hari, dan dilaksanakan 12 kali dalam setahun. Setelah Aksi Perubahan, proses tersebut ditargetkan menjadi maksimal 1 hari kerja dengan kebutuhan sekitar 2 jam per orang, sehingga waktu kerja dapat dialihkan untuk analisis dan tindak lanjut

**Bagian VI: Pimpin Perubahan**

- (Paragraf) Bapak/Ibu, bagaimana Bapak/Ibu akan memimpin agar Aksi Perubahan dapat diterima, dilaksanakan, dan tetap berlanjut setelah masa aktualisasi selesai?
  - Contoh: Saya akan memperkenalkan Aksi Perubahan melalui rapat pimpinan dan forum koordinasi fasilitas pelayanan kesehatan, kemudian melibatkan pengelola mutu sejak penyusunan mekanisme, uji coba, hingga evaluasi agar perubahan dipahami dan digunakan bersama. Dukungan diperkuat melalui komitmen Kepala Dinas dan pimpinan fasilitas pelayanan kesehatan, pendampingan kepada pengelola mutu, serta penyampaian ca


#### PKN Tingkat II

**Bagian I: Identitas / Informasi Umum**

Lihat daftar kolom identitas pada bagian 7.1.

**Bagian II : Tupoksi dan IKU**

- (Paragraf) Bapak/Ibu, apa tugas pokok jabatan yang paling berkaitan langsung dengan permasalahan yang akan diselesaikan?
- (Paragraf) Bapak/Ibu, bagaimana alur proses kerja saat ini dalam pelaksanaan tugas pokok tersebut?
- (Paragraf) Bapak/Ibu, pada bagian mana dalam proses kerja tersebut yang paling sering mengalami kendala atau belum optimal?
- (Paragraf) Bapak/Ibu, indikator kinerja utama apa yang secara langsung terdampak oleh kondisi tersebut?
- (Paragraf) Bapak/Ibu, bagaimana capaian indikator kinerja utama saat ini dibandingkan target, dan apa dampaknya terhadap layanan kepada masyarakat?

**Bagian III : Isu Strategis**

- (Paragraf) Bapak/Ibu, apa masalah utama yang saat ini paling mendesak dan berdampak besar terhadap kinerja organisasi atau layanan kepada masyarakat?
- (Paragraf) Bapak/Ibu, data atau fakta apa yang menunjukkan bahwa masalah tersebut benar-benar terjadi dan perlu segera ditangani?
- (Paragraf) Bapak/Ibu, pada bagian proses atau layanan mana masalah tersebut paling sering terjadi dan siapa pihak yang paling terdampak?
- (Paragraf) Bapak/Ibu, apa kesenjangan utama antara kondisi saat ini dengan kondisi ideal yang seharusnya dicapai?
- (Paragraf) Bapak/Ibu, apa dampak nyata dari masalah tersebut terhadap kinerja organisasi dan kualitas layanan kepada masyarakat?

**Bagian IV : Peran Strategis**

- (Paragraf) Bapak/Ibu, apa kewenangan jabatan yang dimiliki dalam menangani masalah tersebut?
- (Paragraf) Bapak/Ibu, bagaimana posisi jabatan dalam struktur organisasi mempengaruhi kemampuan dalam mengendalikan atau menyelesaikan masalah tersebut?
- (Paragraf) Bapak/Ibu, apa peran konkret yang dapat dilakukan secara langsung untuk mendorong perubahan terhadap masalah tersebut?
- (Paragraf) Bapak/Ibu, pihak internal dan eksternal mana saja yang perlu dilibatkan agar perubahan dapat berjalan efektif?
- (Paragraf) Bapak/Ibu, apa kekuatan dan keterbatasan jabatan dalam mendorong perubahan terhadap masalah tersebut?

**Bagian V : Akar Masalah**

- (Paragraf) Bapak/Ibu, apa saja penyebab utama yang membuat masalah tersebut terus terjadi hingga saat ini?
- (Paragraf) Bapak/Ibu, bagaimana penyebab tersebut berkaitan dengan proses kerja yang belum berjalan efektif?
- (Paragraf) Bapak/Ibu, bagaimana penyebab tersebut berkaitan dengan kapasitas SDM atau kompetensi yang dimiliki saat ini?
- (Paragraf) Bapak/Ibu, bagaimana penyebab tersebut berkaitan dengan sistem, regulasi, atau tata kelola yang berlaku saat ini?
- (Paragraf) Bapak/Ibu, dari seluruh penyebab yang telah diidentifikasi, mana yang paling dominan dan harus diprioritaskan untuk diselesaikan?

**Bagian VI : Arah Perubahan**

- (Paragraf) Bapak/Ibu, seperti apa kondisi ideal yang ingin diwujudkan setelah proyek perubahan dilaksanakan?
- (Paragraf) Bapak/Ibu, perubahan utama apa yang ingin diwujudkan pada proses kerja, layanan, atau tata kelola?
- (Paragraf) Bapak/Ibu, pendekatan kebijakan apa yang akan digunakan untuk mendorong perubahan tersebut?
- (Paragraf) Bapak/Ibu, bagaimana keterlibatan lintas OPD, kabupaten/kota, atau Kementerian/Lembaga dalam mendukung perubahan tersebut?
- (Paragraf) Bapak/Ibu, indikator keberhasilan apa yang menunjukkan bahwa perubahan tersebut benar-benar tercapai?

**Bagian VII : Kepemimpinan dan Pengembangan Diri**

- (Paragraf) Bapak/Ibu, berapa nilai hasil penilaian kepemimpinan yang diperoleh dari mentor dan/atau lembaga penilai, serta apa kategori penilaiannya?
- (Paragraf) Bapak/Ibu, bagaimana rincian nilai pada setiap sub komponen penilaian kepemimpinan yang diberikan oleh mentor atau lembaga penilai?
- (Paragraf) Bapak/Ibu, apakah terdapat perbedaan antara penilaian diri, mentor, dan/atau lembaga, serta bagaimana kesenjangan tersebut?
- (Paragraf) Bapak/Ibu, area mana yang paling perlu dikembangkan berdasarkan hasil penilaian tersebut?
- (Paragraf) Bapak/Ibu, rencana pengembangan apa yang akan dilakukan untuk meningkatkan kompetensi kepemimpinan tersebut?


## 23. Lampiran B: struktur output rancangan

Output disusun per bagian (satu panggilan per bagian) dan diekspor ke Word dengan urutan berikut.

### B.1 Rancangan Aktualisasi (Latsar)

1. Lima alternatif judul akronim, masing-masing dengan kepanjangan dan makna.
2. Tujuan umum.
3. Tujuan khusus.
4. Manfaat (bagi peserta, unit kerja, pengguna layanan).
5. Dampak bila isu tidak diselesaikan.
6. Analisis Fishbone 6M (Man, Machine, Method, Material, Measurement, Mother Nature/Environment) dan akar masalah prioritas.
7. Lima kegiatan kreatif (Rp0, berdampak langsung, tidak saling bergantung output).
8. Tahapan setiap kegiatan (minimal 5 tahap) dengan output konkret.
9. Relevansi dengan Agenda 2 (nilai BerAKHLAK, perwujudan perilaku resmi) per kegiatan.
10. Relevansi dengan Agenda 3 (Smart ASN dan Manajemen ASN) per kegiatan.
11. Simpulan.
12. Komitmen.
13. Rencana keberlanjutan.

### B.2 Rancangan Aksi Perubahan (PKP dan PKA)

- Lima alternatif judul akronim
- I. Pendahuluan
- II. Profil dan kinerja organisasi
- III. Diagnosis masalah kinerja (termasuk Fishbone)
- IV. Analisis strategis (SWOT dan strategi)
- V. Terobosan inovatif
- VI. Stakeholder (Promoters, Latents, Defenders, Apathetics) dan sumber daya
- VII. Milestone jangka pendek, menengah, panjang
- VIII. Manajemen risiko
- IX. Relevansi Asta Cita dan Reformasi Birokrasi
- X. Value ekonomi dan Net Economic Value
- XI. Strategi marketing
- XII. Pemetaan sikap dan perilaku kepemimpinan
- Penutup

### B.3 Rancangan Proyek Perubahan (PKN Tk II)

- I. Ringkasan (1.1–1.5)
- II. Deskripsi organisasi (2.1–2.4)
- III. Pendahuluan (3.1–3.4)
- IV. Relevansi dengan Asta Cita, Indonesia Emas 2045, dan SDGs
- V. Relevansi dengan Reformasi Birokrasi Berdampak
- VI. Analisis permasalahan dan SWOT (6.1–6.10)
- VII. Terobosan inovatif (7.1–7.5)
- VIII. Valuasi nilai ekonomi (8.1–8.3)
- IX. Milestone (9.1–9.5)
- X. Strategi marketing (10.1–10.5)
- XI. Mata pelatihan pendukung (11.1–11.4)
- XII. Strategi pengembangan kompetensi (12.1–12.3)
- XIII. Pemetaan sikap, perilaku kepemimpinan, dan potensi diri (13.1–13.4)
- XIV. Penutup (14.1–14.3)

Rincian subbagian mengikuti contoh output pada file "Output Inputan" di folder setiap pelatihan. Template output disimpan di `plan_templates.output_json` agar bisa diubah tanpa mengubah kode.
