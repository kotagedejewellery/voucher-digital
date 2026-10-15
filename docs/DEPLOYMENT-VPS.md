# Deployment VPS melalui Traefik

Konfigurasi ini menjalankan image Apache yang sudah ada di `Dockerfile`. Tidak diperlukan PHP-FPM, Nginx, bind mount source, atau port host untuk aplikasi. Traefik mengarahkan HTTPS langsung ke port 80 container `app`; PostgreSQL hanya berada pada network internal.

## Prasyarat VPS

- Docker Engine dan Docker Compose plugin tersedia.
- Traefik sudah berjalan dan memiliki external Docker network bernama `proxy`, entrypoint `web`/`websecure`, serta certificate resolver bernama `letsencrypt`.
- DNS `A`/`AAAA` untuk domain voucher sudah mengarah ke VPS sebelum Traefik meminta sertifikat.
- Repository ini berada di `/opt/apps/sistem-voucher-digital/voucher-digital` dan branch deploy-nya `main`.

Jika nama network, entrypoint, atau certificate resolver Traefik berbeda, sesuaikan **hanya** value yang sepadan dalam `compose.production.yml` sebelum menjalankan deploy.

## Menyiapkan environment

1. Salin `.env.production.example` menjadi `.env.production` di root proyek.
2. Ganti `VOUCHER_DOMAIN` dan `APP_URL` dengan domain final yang sama. `APP_URL` wajib memakai HTTPS dan tanpa trailing slash.
3. Buat nilai rahasia di VPS, lalu isi `APP_KEY` dan `DB_PASSWORD`:

   ```sh
   openssl rand -base64 32
   openssl rand -hex 32
   ```

   Nilai pertama dipakai sebagai `base64:<hasil-pertama>` untuk `APP_KEY`; nilai kedua untuk `DB_PASSWORD`. Simpan keduanya di password manager. Jangan mengubah `APP_KEY` setelah sistem memiliki data.

4. Tentukan network CIDR atau IP Traefik dan isi `TRUSTED_PROXIES`. Periksa dengan:

   ```sh
   docker network inspect proxy
   ```

   Masukkan CIDR network yang dipakai Traefik, misalnya `172.18.0.0/16`. Aplikasi tidak memiliki port publik, sehingga hanya Traefik yang dapat meneruskan header proxy terpercaya.

5. Isi credentials Meta/WACO hanya bila integrasi tersebut siap dipakai. Biarkan `WACO_ENABLED=false` sampai template dan sender WACO telah benar-benar aktif.

Semua perintah Compose produksi berikut harus menyertakan `--env-file .env.production`; flag ini juga mengisi nilai PostgreSQL saat Compose memproses file.

## Deploy pertama

Jalankan dari root repository di VPS:

```sh
docker compose --env-file .env.production -f compose.production.yml build
docker compose --env-file .env.production -f compose.production.yml up -d postgres
docker compose --env-file .env.production -f compose.production.yml run --rm app php artisan migrate --seed --force
docker compose --env-file .env.production -f compose.production.yml up -d app worker scheduler
docker compose --env-file .env.production -f compose.production.yml ps
```

`migrate --seed` hanya dijalankan pada deploy pertama untuk membuat data awal. Buat akun operator melalui command aplikasi setelah container `app` sehat:

```sh
docker compose --env-file .env.production -f compose.production.yml exec app php artisan voucher:admin
```

Lalu periksa `https://<VOUCHER_DOMAIN>/up`, login admin, QRIS merchant asli, dan alur verifikasi manual. Uji Meta melalui Test Events sebelum mengaktifkan campaign. Jangan mencoba pembayaran nyata atau mengaktifkan WACO tanpa kredensial dan template produksi.

## Deploy pembaruan

Backup terlebih dahulu. Lalu build image baru, jalankan migrasi sekali, dan ganti proses aplikasi:

```sh
docker compose --env-file .env.production -f compose.production.yml build
docker compose --env-file .env.production -f compose.production.yml up -d postgres
docker compose --env-file .env.production -f compose.production.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yml up -d app worker scheduler
```

Periksa `ps`, log `app`/`worker`, dan endpoint `/up` sesudahnya. Jangan gunakan `down -v`: perintah itu menghapus volume database maupun storage privat.

## Backup minimum

Cadangkan database, named volume `postgres-data`, named volume `app-storage`, dan salinan terenkripsi `.env.production`. Uji restore pada VPS/non-produksi terpisah sebelum mengandalkan backup. Docker memberi nama volume dengan prefix project; lihat nama aktual menggunakan `docker volume ls`.

Contoh backup database ke file di host:

```sh
docker compose --env-file .env.production -f compose.production.yml exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > voucher-$(date +%F).sql
```

Command membaca nama database dan user dari environment container PostgreSQL, sehingga tidak memerlukan secret di shell host.

## Deploy otomatis GitHub Actions

Workflow [`.github/workflows/deploy-vps.yml`](../.github/workflows/deploy-vps.yml) berjalan saat ada push ke `main` atau ketika dijalankan manual dari tab Actions. Ia masuk sebagai user VPS yang sudah ada, memperbarui checkout pada `/opt/apps/sistem-voucher-digital/voucher-digital` dengan `git pull --ff-only`, build image, menjalankan migrasi, lalu menunggu `app`, `worker`, dan `scheduler` siap.

Tambahkan satu secret repository tambahan sebelum workflow pertama dijalankan:

| Secret | Isi |
|---|---|
| `VPS_HOST` | Host/IP VPS yang sudah tersedia |
| `VPS_USER` | User SSH `Kotagede-Jewellery` yang sudah tersedia |
| `VPS_SSH_KEY` | Private key untuk user tersebut yang sudah tersedia |
| `VPS_SSH_KNOWN_HOSTS` | Baris host public key VPS pada format `known_hosts` |

`VPS_SSH_KNOWN_HOSTS` wajib agar runner memeriksa identitas VPS secara ketat dan tidak menerima host key baru secara otomatis. Ambil host key `ed25519` dari VPS melalui channel tepercaya, lalu bandingkan fingerprint-nya sebelum menyimpan sebagai secret.

Sebelum push pertama yang memicu deploy otomatis, lakukan deploy pertama secara manual sesuai bagian sebelumnya: buat `.env.production`, jalankan `migrate --seed --force`, dan buat admin. Workflow pembaruan hanya menjalankan `migrate --force`; ia tidak mengubah `.env.production`, tidak menjalankan seed, dan gagal aman bila checkout VPS memiliki perubahan Git yang belum tersimpan.
