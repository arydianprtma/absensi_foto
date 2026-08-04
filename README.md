# 📸 Absensi Foto AI - Sistem Absensi Pengenalan Wajah & Anti-Spoofing

Sistem Absensi Siswa berbasis **AI Face Recognition (InsightFace)** dan **Anti-Spoofing** cerdas berbasis Laravel 13, Inertia.js v3, Vue 3, dan Service Python FastAPI.

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs)
![Python](https://img.shields.io/badge/Python-FastAPI-3776AB?style=for-the-badge&logo=python)
![InsightFace](https://img.shields.io/badge/AI-InsightFace-indigo?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)

---

## 🌟 Fitur Utama

- 🤖 **InsightFace AI Recognition (512-D Embeddings)**: Ekstraksi fitur wajah 512-dimensi yang presisi tinggi dengan ambang batas batas sah minimum **>= 50.0% Match**.
- 🛡️ **Proteksi Pasif & Aktif Anti-Spoofing (Anti-Foto HP / Replay Attack)**:
  - *Pasif*: Analisis varians Laplacian untuk mendeteksi tekstur layar HP/kertas cetak.
  - *Aktif*: Tantangan liveness acak secara real-time (Kedip mata, Tengok Kiri/Kanan, dan Senyum).
- 🔊 **Suara Sambutan AI Ramah (Google Neural TTS Engine)**: Menyapa nama siswa secara eksplisit dengan artikulasi Bahasa Indonesia yang alami saat berhasil absen masuk/pulang.
- 📊 **Dashboard Analytics & Grafik Mingguan**: Grafik batang tren kehadiran siswa 7 hari terakhir (Hadir Tepat Waktu vs Terlambat).
- 📄 **Export Laporan Excel (.xlsx / CSV)**: Fitur cetak & export laporan absensi siswa lengkap dengan filter kelas & tanggal.
- 📅 **Kalender & Pengelolaan Hari Libur Sekolah**: Fitur manajemen hari libur sekolah agar absensi teratur.
- ⚙️ **Pengaturan Jam Masuk & Pulang (Format 24-Jam)**: Fleksibilitas menentukan rentang jam absensi sekolah.
- 🎨 **Modern SaaS Design (Light & Dark Mode)**: Tampilan antarmuka clean, elegan, dan fleksibel.

---

## 🏗️ Teknologi & Arsitektur

### 1. Backend Web Application
- **Framework**: Laravel 13
- **PHP Version**: PHP 8.3 / 8.5
- **Database**: MySQL / MariaDB

### 2. Frontend Application
- **Framework**: Vue 3 (Composition API with `<script setup lang="ts">`)
- **SPA Adapter**: Inertia.js v3
- **Styling**: TailwindCSS v4
- **Icons**: Lucide Vue

### 3. Python AI Face Microservice
- **Framework**: FastAPI / Uvicorn (Port `5000`)
- **Engine**: InsightFace (`buffalo_l` / ArcFace embeddings)
- **Computer Vision**: OpenCV, NumPy

---

## 🚀 Panduan Instalasi & Penggunaan

### 1. Prasyarat Sistem
- PHP >= 8.3 & Composer
- Node.js >= 20.x & NPM
- Python >= 3.10 & PIP
- MySQL / MariaDB Database

---

### 2. Instalasi Web Application (Laravel & Vue)

```bash
# 1. Clone Repositori
git clone https://github.com/arydianprtma/absensi_foto.git
cd absensi_foto

# 2. Install Dependensi PHP & Node.js
composer install
npm install

# 3. Konfigurasi Environment
cp .env.example .env
php artisan key:generate

# 4. Sesuaikan Konfigurasi Database di file .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_foto
DB_USERNAME=root
DB_PASSWORD=

# 5. Jalankan Migrasi Database & Seeder
php artisan migrate --seed

# 6. Build Asset Frontend
npm run build
```

---

### 3. Instalasi Python AI Microservice (InsightFace)

```bash
# 1. Masuk ke direktori python_service
cd python_service

# 2. Install Dependensi Python
pip install -r requirements.txt

# 3. Jalankan Service AI (Port 5000)
python app.py
```

---

### 4. Menjalankan Aplikasi

Pastikan **Service Python AI** dan **Laravel Application** berjalan bersamaan:

```bash
# Jalankan Laravel Web Server (Port 8000)
php artisan serve --port=8000

# Atau gunakan composer dev runner (Vite + Server)
composer run dev
```

Akses aplikasi di browser: `http://127.0.0.1:8000`

---

## 📑 Struktur Direktori Penting

```
absensi_foto/
├── app/
│   ├── Http/Controllers/    # AttendanceController, StudentController, HolidayController
│   └── Models/              # Student, Attendance, Holiday, AttendanceSetting
├── database/
│   └── migrations/          # Schema tabel database
├── python_service/
│   ├── app.py               # FastAPI InsightFace Recognition & Anti-Spoofing Service
│   └── requirements.txt     # Python packages
├── resources/js/
│   ├── components/          # Vue UI Reusable Components
│   └── pages/               # Inertia Pages (Absensi, Dashboard, Reports, Holidays)
├── routes/
│   └── web.php              # Web Routes Application
└── README.md                # Dokumentasi Proyek
```

---

## 📜 Lisensi

Proyek ini dilindungi di bawah lisensi **[MIT License](LICENSE)**.
