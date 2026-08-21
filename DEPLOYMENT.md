# Panduan Deployment — Tourosa

Arsitektur hosting:

| Bagian | Hosting | URL |
|---|---|---|
| Frontend (Vue 3 + Vite) | Firebase Hosting | `https://PROJECT_ID.web.app` |
| Backend API (Laravel) | cPanel (shared hosting) | `https://api.domainanda.com` |

Frontend memanggil API via absolute URL dari env `VITE_API_URL`, auth menggunakan **Bearer token** (bukan cookie), sehingga tidak perlu konfigurasi stateful domain Sanctum — cukup CORS.

---

## 1. Backend Laravel → cPanel (Shared Hosting)

> **Requirement:** PHP **8.2+** (cek lewat cPanel → *MultiPHP Manager* / *Select PHP Version*). Ekstensi wajib biasanya sudah aktif default: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `dom`, `fileinfo`, `xml`, `zip` (zip dibutuhkan maatwebsite/excel).

### 1.1 Siapkan build dari lokal

Karena shared hosting umumnya **tidak punya Composer**, install dependency di lokal dulu lalu upload semuanya:

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan key:generate        # catat nilai APP_KEY untuk .env di server
```

Folder yang di-upload ke cPanel: **seluruh isi `backend/` termasuk `vendor/`** (kecuali `node_modules`, `.git`, dan file test jika mau hemat).

### 1.2 Penempatan file & document root

Struktur yang disarankan — kode aplikasi di luar `public_html` supaya `.env` tidak bisa diakses publik:

```
/home/cpaneluser/
├── tourosa-api/          ← seluruh project Laravel (app, vendor, .env, dst)
└── public_html/          ← docroot domain
```

**Opsi A — pakai subdomain (paling mudah):**
Buat subdomain `api.domainanda.com` di cPanel → *Domains*, arahkan document root-nya langsung ke `/home/cpaneluser/tourosa-api/public`. Selesai.

**Opsi B — pakai domain utama (docroot terkunci di `public_html`):**
1. Copy **isi** folder `tourosa-api/public/` ke dalam `public_html/`.
2. Edit `public_html/index.php`, ubah dua path agar menunjuk ke folder aplikasi:
```php
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
require __DIR__.'/../tourosa-api/vendor/autoload.php';
$app = require_once __DIR__.'/../tourosa-api/bootstrap/app.php';
// ... sisanya tetap
```
3. Pastikan `.htaccess` bawaan Laravel ikut ter-copy ke `public_html/`.

### 1.3 Konfigurasi `.env`

Buat/edit `.env` di `~/tourosa-api/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.domainanda.com
APP_KEY=base64:xxxx        ← hasil php artisan key:generate dari lokal
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_tourosa
DB_USERNAME=cpaneluser_tourosa
DB_PASSWORD=secret
FRONTEND_URL=https://PROJECT_ID.web.app
```

> Database dibuat dulu di cPanel → *MySQL® Databases* (buat DB + user, lalu assign user ke DB dengan ALL PRIVILEGES).

### 1.4 Migrasi database

**Jika cPanel punya fitur *Terminal* (kebanyakan hosting modern ada):**
```bash
cd ~/tourosa-api
php artisan migrate --force
php artisan config:cache
```

**Jika tidak ada Terminal sama sekali:** buat SQL dump dari lokal lalu import via **phpMyAdmin**:
```bash
# di lokal
php artisan migrate --force        # pastikan struktur final
mysqldump -u root tourosa > schema.sql   # atau export via HeidiSQL/TablePlus
```
Import `schema.sql` lewat phpMyAdmin cPanel ke database yang sudah dibuat. Tanpa Terminal, `config:cache` boleh dilewati (tanpa cache hanya lebih lambat, tetap jalan).

### 1.5 Permission & storage link

Set permission folder berikut ke **775** via File Manager (klik kanan → Change Permissions): `storage/` (rekursif) dan `bootstrap/cache/`.

Untuk `storage:link` (jika ada file upload/gambar):
- Via Terminal: `php artisan storage:link`
- Tanpa Terminal: buat file sementara `public_html/link.php` berisi `<?php symlink(__DIR__.'/../tourosa-api/storage/app/public', __DIR__.'/storage');` → akses sekali di browser → hapus file-nya. Jika fungsi `symlink()` dinonaktifkan host, fallback: jalankan `php artisan storage:link --relative` di lokal lalu upload hasilnya, atau serve file lewat route khusus.

---

## 2. Frontend Vue → Firebase Hosting

### 2.1 Persiapan environment

Buat file `frontend/.env.production`:

```env
VITE_API_URL=https://api.domainanda.com/api
```

> File `.env` (lokal) tetap dipakai untuk development (`http://127.0.0.1:8000/api`). Saat `npm run build`, Vite otomatis memakai `.env.production`.

### 2.2 Konfigurasi `firebase.json`

Hapus blok rewrite `/api/**` ke Cloud Run (API sudah di cPanel). Isi final:

```json
{
  "hosting": {
    "public": "dist",
    "ignore": [
      "firebase.json",
      "**/.*",
      "**/node_modules/**"
    ],
    "rewrites": [
      {
        "source": "**",
        "destination": "/index.html"
      }
    ],
    "headers": [
      {
        "source": "/assets/**",
        "headers": [
          {
            "key": "Cache-Control",
            "value": "public, max-age=31536000, immutable"
          }
        ]
      }
    ]
  }
}
```

Rewrite `**` → `/index.html` wajib agar Vue Router (history mode) tidak 404 saat halaman di-refresh.

### 2.3 Firebase Analytics

**PENTING — lokasi file:** pasang script ini di `frontend/index.html` (root project, sejajar dengan `package.json`), **BUKAN** di `frontend/dist/index.html`.

```
frontend/
├── index.html          ← taruh script di sini (source)
├── src/
├── package.json
└── dist/                ← hasil build otomatis, jangan diedit manual
```

Alasannya: `dist/` adalah folder hasil `npm run build`, dan Vite **menghapus lalu membuat ulang** seluruh isi `dist/` dari `index.html` root setiap kali build dijalankan. Kalau script ditaruh langsung di `dist/index.html`, dia akan **hilang** begitu ada `npm run build` berikutnya (mis. karena update kode lain) — dan biasanya baru ketauan belakangan saat Analytics tiba-tiba berhenti mencatat data.

Sudah terpasang di `frontend/index.html` via **script tag CDN** (tanpa install npm SDK). Inisialisasi berjalan otomatis saat halaman dibuka:

```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <title>Tourosa</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>

    <!-- Firebase Analytics -->
    <script type="module">
      import { initializeApp } from "https://www.gstatic.com/firebasejs/12.18.0/firebase-app.js";
      import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.18.0/firebase-analytics.js";
      const firebaseConfig = {
        apiKey: "YOUR_FIREBASE_API_KEY",
        authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
        projectId: "YOUR_PROJECT_ID",
        storageBucket: "YOUR_PROJECT_ID.firebasestorage.app",
        messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
        appId: "YOUR_FIREBASE_APP_ID",
        measurementId: "YOUR_MEASUREMENT_ID"
      };
      const app = initializeApp(firebaseConfig);
      const analytics = getAnalytics(app);
      window.__firebaseAnalytics = analytics;   // expose untuk pemakaian lanjutan
    </script>
  </body>
</html>
```

Taruh blok `<script>` Firebase sebelum penutup `</body>`, setelah script utama Vue (`/src/main.js`). Dengan begitu, setiap kali `npm run build` dijalankan, script ini otomatis ikut ter-copy ke `dist/index.html` yang baru — tidak perlu diinget-inget manual tiap deploy.

Catatan:
- Config Firebase memang bersifat publik (bukan secret), aman berada di HTML klien.
- Karena ini SPA, hanya load pertama yang tercatat sebagai `page_view`. Navigasi antar route tidak otomatis terekam — jika ingin dicatat, panggil `logEvent(window.__firebaseAnalytics, 'page_view', { page_path: to.fullPath })` pada `router.afterEach`.
- Data hanya masuk saat site diakses lewat domain Firebase (`*.web.app` / custom domain); localhost umumnya di-skip.
- Lihat hasilnya di Firebase Console → project (sesuai `PROJECT_ID`) → *Analytics* (atau Google Analytics property dengan ID `measurementId` di atas). Data realtime muncul di tab *Realtime*, laporan lengkap butuh ±24 jam.

### 2.4 Deploy

```bash
npm install -g firebase-tools
firebase login
cd frontend
firebase init hosting
#   - Use an existing project → pilih project Firebase
#   - public directory: dist
#   - configure as single-page app: Yes
#   - GitHub deploys: No (opsional)
npm run build
firebase deploy --only hosting
```

Setelah selesai, site live di `https://PROJECT_ID.web.app`.

---

## 3. Update Domain & CORS

| Jika frontend pakai | Set di `.env` backend |
|---|---|
| `https://PROJECT_ID.web.app` | `FRONTEND_URL=https://PROJECT_ID.web.app` |
| Custom domain, mis. `https://app.domainanda.com` | `FRONTEND_URL=https://app.domainanda.com` |

Custom domain Firebase: Firebase Console → Hosting → *Add custom domain*, lalu arahkan DNS sesuai instruksi.

Setiap mengubah `FRONTEND_URL` di backend, jalankan ulang:

```bash
php artisan config:cache
```

---

## 4. Alur Update Berikutnya

```bash
# Frontend
cd frontend && npm run build && firebase deploy --only hosting

# Backend (shared hosting)
composer install --no-dev --optimize-autoloader   # jika ada dependency baru
# upload ulang folder yang berubah via File Manager/FTP:
#   app/, config/, database/migrations/, routes/, public/build (jika ada), vendor/ (jika di-update)
php artisan migrate --force && php artisan config:cache   # via Terminal cPanel
```

---

## 5. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| CORS error di console | `FRONTEND_URL` tidak cocok dengan origin frontend | Samakan persis (protokol + domain + port), lalu `config:cache` |
| Refresh halaman dashboard → 404 | Rewrite SPA belum aktif | Pastikan rewrite `**` → `/index.html` di `firebase.json` |
| API 500 / blank | `APP_KEY` kosong, PHP version < 8.2 | Isi `APP_KEY` dari lokal, cek MultiPHP Manager |
| `vendor/` gak ke-upload sempurna | Upload massal via File Manager sering gagal/timeout | Kompres jadi `.zip` → upload → extract di File Manager |
| Gagal konek database | Kredensial MySQL cPanel salah | Cek DB_DATABASE/USERNAME/PASSWORD di cPanel → MySQL Databases |
| Token login gak tersimpan / 401 | `VITE_API_URL` masih menunjuk localhost | Pastikan build memakai `.env.production` |
| Mixed content blocked | Frontend HTTPS memanggil API HTTP | API harus `https://` |