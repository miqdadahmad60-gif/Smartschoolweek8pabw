# Smart School Kupang — Laravel

Port Laravel ini memindahkan fitur utama aplikasi CodeIgniter 3 pada ZIP sumber: landing page, autentikasi dan registrasi tiga peran, dashboard per peran, pengelolaan dan unduh materi, pembuatan/edit/hapus/kerjakan kuis, penyimpanan jawaban dan nilai, pemantauan nilai orang tua, pengajuan/edit/hapus fasilitas, pemrosesan status oleh guru, serta endpoint JSON login dan profil sesi.

## Database (MySQL / phpMyAdmin)

Database memakai **MySQL** (XAMPP) agar tabel `users` bisa dilihat di phpMyAdmin. Konfigurasi ada di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartschool_kupang
DB_USERNAME=root
DB_PASSWORD=
```

Langkah menjalankan (Windows / XAMPP, PHP 8.2 atau lebih baru; project memakai Laravel 12):

1. Start **Apache** dan **MySQL** di XAMPP, buka phpMyAdmin, buat database kosong `smartschool_kupang` (collation `utf8mb4_unicode_ci`).
2. Di folder proyek:

```powershell
composer install                 # akan membuat composer.lock & folder vendor
Copy-Item .env.example .env      # lewati jika .env sudah ada
php artisan key:generate         # lewati jika APP_KEY sudah terisi
php artisan migrate:fresh --seed # buat tabel + isi data (termasuk akun dummy Faker)
php artisan serve
```

3. Buka http://127.0.0.1:8000/login lalu uji login memakai akun di bawah.

> Jika sebelumnya memakai SQLite: file `database/database.sqlite` lama tidak memiliki kolom `remember_token`; gunakan `migrate:fresh --seed` agar skema terbaru terbentuk.

## Authentication

Dibuat **manual** (tanpa Laravel Breeze) memakai guard `web` + model `User`, agar tampilan portal dan peran (siswa/guru/ortu) yang sudah ada tidak tertimpa.

| Fitur | Lokasi |
|---|---|
| Register, Login, Logout, API login | `app/Http/Controllers/AuthController.php` |
| Form + pesan error per kolom | `resources/views/auth.blade.php` |
| Route: `guest` untuk login/register, `auth` untuk halaman portal | `routes/web.php` |
| Redirect tamu -> `/login`, user login -> `/dashboard` | `bootstrap/app.php` |
| Seeder 6 akun dummy (Faker) | `database/seeders/UserSeeder.php` (dipanggil dari `DatabaseSeeder.php`) |
| Uji otomatis | `tests/Feature/AuthTest.php` (`php artisan test`) |

Halaman wajib login (middleware `auth`): `/dashboard`, `/academic/*`, `/quizzes/*`, `/grades`, `/facilities/*`, `POST /logout`. Halaman publik: `/`, `/login`, `/register`, `/api/login`.

Akun demo:

- Siswa: `siswa@smartschool.test` / `siswa123`
- Guru: `guru@smartschool.test` / `guru123`
- Orang tua: `ortu@smartschool.test` / `ortu123`
- Akun seeder Faker (password `password123`): `siswa1`, `siswa2`, `siswa3`, `guru1`, `guru2`, `ortu1` @`smartschool.test`

Password selalu disimpan sebagai hash bcrypt (`$2y$12$...`) di kolom `users.password`.

## Endpoint dan fitur

- Halaman: `/`, `/login`, `/register`, `/dashboard`, `/academic`, `/quizzes`, `/grades`, `/facilities`
- API sesi: `POST /api/login`, `GET /api/me`
- Materi: upload, edit, hapus, download; hanya guru pemilik materi yang dapat mengubahnya.
- Kuis: guru dapat buat/edit/hapus dan melihat hasil; siswa dapat mengerjakan satu kali; hasil tersimpan dan tampil di nilai siswa/orang tua.
- Fasilitas: siswa dapat mengajukan, mengedit atau membatalkan permintaan yang masih menunggu; guru dapat mengubah status dan menghapus permintaan.

## Perubahan dari CodeIgniter ke Laravel

- `application/config/routes.php` dipindahkan ke `routes/web.php`; kontrol alur dipusatkan di `app/Http/Controllers/SchoolController.php`.
- `School_model` dan query builder CI diganti query builder Laravel melalui `DB`.
- Helper CI, loader, dan sesi CI diganti route/controller Laravel, middleware `auth`, guard session Laravel, validasi request, redirect, respons JSON, dan Blade.
- Upload berkas pindah dari folder `uploads/materials` ke disk `public` Laravel.
- Skema yang sebelumnya disediakan dalam SQL MySQL dikonversi menjadi migrasi Laravel yang dapat dijalankan di SQLite; database lokal berisi tabel dan data demo yang sepadan.
- Tampilan sumber dipindahkan ke Blade dan aset CSS/JS/SVG dipertahankan. Beberapa detail visual diringkas, tetapi alur fitur utama dipertahankan.

Lihat [database/legacy/smartschool_kupang_database.sql](database/legacy/smartschool_kupang_database.sql) untuk dump MySQL sumber (opsional; cukup `php artisan migrate:fresh --seed`).


## Tugas CRUD dengan Upload File (Materi Pembelajaran)

CRUD file tugas diintegrasikan ke menu Materi Pembelajaran.

- Route resource: `Route::resource('materials', MaterialController::class)` pada `routes/web.php` dan berada di dalam middleware `auth`.
- Controller: `app/Http/Controllers/MaterialController.php`.
- View: `resources/views/materials/` (index, create, show, edit, dan form partial).
- Tabel database: `materials`. Kolom file yang digunakan: `file_name` (path relatif), `original_name`, `file_type`, dan `file_size`.
- File disimpan pada `storage/app/public/materials` melalui disk `public`.
- Validasi file: JPG/JPEG/PNG/PDF/DOC/DOCX/PPT/PPTX/XLS/XLSX/TXT/ZIP, maksimal 10 MB.
- Saat file diganti, file lama dihapus. Saat data materi dihapus, file fisik dan baris database ikut dihapus.
- Siswa dan guru yang sudah login dapat melihat detail serta mengunduh file. Hanya guru pemilik materi yang dapat menambah, mengubah, dan menghapus.

Cara uji:
1. Login dengan akun guru.
2. Buka `/materials`, pilih **Tambah Materi**, isi data dan unggah file.
3. Cek phpMyAdmin pada tabel `materials`: kolom `file_name` berisi path seperti `materials/nama-file.pdf`.
4. Cek file fisik pada `storage/app/public/materials`.
5. Coba detail dan unduh, edit/ganti file, lalu hapus untuk memastikan file lama ikut terhapus.
6. Logout dan coba akses `/materials`; pengguna tanpa login akan diarahkan ke login.

Jalankan sekali jika belum:
```powershell
php artisan storage:link
php artisan migrate --seed
```


---

## Tugas Akhir: HMVC + Authorization berbasis Role + AJAX

Arsitektur HMVC memakai **nwidart/laravel-modules** (+ `wikimedia/composer-merge-plugin`, sudah diatur di `composer.json`).

| Modul | Jenis | Isi | Lokasi |
|---|---|---|---|
| **Material** | CRUD *synchronous* (form + redirect) | Materi pembelajaran + upload file (jpg/png/pdf/doc/.. maks 10 MB) - 5 data seeder | `Modules/Material` |
| **Facility** | CRUD **AJAX** (Fetch/XHR, JSON) | Pengajuan peminjaman fasilitas + **upload file surat/bukti** (jpg/png/pdf maks 2 MB) yang dapat dibuka kembali - 5 data seeder | `Modules/Facility` |

Struktur tiap modul: `app/Http/Controllers`, `app/Providers`, `routes/web.php`, `resources/views` (namespace `material::` / `facility::`), `module.json`, `composer.json`.
Modul Facility juga punya `database/migrations` (menambah kolom `attachment_path` dan `attachment_name`) dan `app/Http/Requests`.

### Role & middleware

Middleware `role` (`app/Http/Middleware/RoleMiddleware.php`, alias di `bootstrap/app.php`) membaca kolom `users.role`. Semua halaman pengelolaan data berada di middleware `auth` (wajib login).

| Halaman / aksi | siswa | guru | ortu |
|---|:-:|:-:|:-:|
| Materi: lihat, detail, unduh (`role:guru,siswa`) | ya | ya | 403 |
| Materi: tambah, ubah, hapus (`role:guru`) | 403 | ya | 403 |
| Fasilitas: lihat daftar (`role:guru,siswa`) | ya (miliknya) | ya (semua) | 403 |
| Fasilitas: ajukan, ubah (`role:siswa`) | ya | 403 | 403 |
| Fasilitas: ubah status (`role:guru`) | 403 | ya | 403 |
| Fasilitas: hapus/batalkan (`role:guru,siswa`) | ya (miliknya, menunggu) | ya | 403 |

### Cara menjalankan

```powershell
composer install            # memasang nwidart/laravel-modules + merge-plugin
php artisan migrate:fresh --seed
php artisan serve
```

Seeder membuat file contoh di `storage/app/public/` (materi dan lampiran). Tidak perlu `storage:link` untuk lampiran fasilitas
(dibuka lewat route ber-`auth`); `storage:link` hanya dibutuhkan untuk pratinjau gambar/PDF di halaman detail materi.

### Panduan pembuktian (demo / screenshot)

1. **403 pada role yang salah**: login `siswa@smartschool.test` lalu buka `/materials/create`; atau login `ortu@smartschool.test` lalu buka `/materials` atau `/facilities`.
2. **Wajib login**: logout, lalu buka `/materials` atau `/facilities` -> diarahkan ke `/login`.
3. **AJAX di tab Network**: login siswa -> `/facilities` -> F12 -> Network -> filter **Fetch/XHR**. Tambah, ubah, dan batalkan pengajuan. Request `list`, `POST /facilities`, `DELETE ...` terlihat. Kirim form kosong atau file > 2 MB untuk melihat respons **422**.
4. **Upload dapat dibuka kembali**: unggah lampiran, klik tautan nama file pada kartu pengajuan (atau tombol Edit untuk melihat file saat ini).
5. **Bukti file & database**: folder `storage/app/public/facility-attachments` dan `storage/app/public/materials`; phpMyAdmin tabel `facility_requests` (kolom `attachment_path`, `attachment_name`) dan `materials` (kolom `file_name`).
#   S m a r t s c h o o l w e e k 8 p a b w  
 