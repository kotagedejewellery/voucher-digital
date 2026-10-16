# Instruksi Agen — Voucher Digital Sat-Set

## Urutan kerja

1. Baca seluruh `AGENTS.md` sebelum melakukan pekerjaan apa pun.
2. Pilih satu skill Matt Pocock yang paling relevan, baca seluruh `SKILL.md`, lalu ikuti workflow-nya.
3. Untuk pekerjaan kode, gunakan Ponytail sebagai standar analisis dependency dan blast radius. Gunakan level `full` untuk perubahan behavior, business logic, data flow, permission, shared code, bug, atau refactor. Analisis perubahan trivial dapat proporsional.
4. Gunakan Graphify sebelum memahami atau mengubah kode/arsitektur yang non-trivial. Baca source langsung hanya jika konteks Graphify belum cukup.
5. Setelah satu logical change selesai, update Graphify sebelum handoff.
6. Untuk pekerjaan UI/UX, jalankan `impeccable context` satu kali per sesi dan gunakan Impeccable.

## Batas perubahan

1. Pilih solusi paling sederhana yang menyelesaikan requirement sepenuhnya.
2. Jaga scope. Jangan melakukan refactor, cleanup, improvement, atau inisiasi lain yang tidak diminta.
3. Pertahankan behavior existing. Jangan mengubah API contract, URL, schema, permission, UX flow, default value, atau behavior di luar kebutuhan task.
4. Ikuti pola existing. Gunakan architecture, convention, component, utility, dan dependency yang sudah tersedia sebelum membuat pola baru.
5. Jangan menambah dependency jika kebutuhan dapat diselesaikan dengan stack existing.
6. Berhenti ketika acceptance criteria terpenuhi. Jangan melanjutkan improvement setelah task selesai.

## Perbaikan bug

Saat memperbaiki bug:

1. Temukan root cause, bukan hanya gejala.
2. Telusuri caller, shared component, handler, utility, query, test, dan alur lain yang menggunakan pola yang sama.
3. Perbaiki seluruh kasus yang memiliki root cause yang sama.
4. Laporkan kasus yang terlihat mirip tetapi memiliki behavior berbeda; jangan otomatis mengubahnya.
5. Tambahkan atau jalankan test yang membuktikan bug tidak muncul kembali.

## Perlindungan data dan database

Data existing tidak boleh dianggap disposable, termasuk database lokal.

Tanpa persetujuan eksplisit user, jangan:

- reset atau recreate database;
- menjalankan `migrate:fresh`, database reset, `DROP DATABASE`, atau `DROP SCHEMA`;
- melakukan truncate atau delete massal;
- menghapus database Docker volume;
- menjalankan destructive seed;
- membuat destructive migration; atau
- mengubah migration lama yang sudah pernah digunakan di shared, staging, atau production environment.

Gunakan migration forward-only dan non-destructive secara default. Sebelum migration, seed, atau mutation data berisiko:

1. Verifikasi environment dan target database.
2. Pahami dampaknya terhadap data existing.
3. Pilih solusi non-destructive.

Jika satu-satunya solusi berisiko kehilangan data: berhenti, jelaskan risiko, dan minta persetujuan eksplisit.

Deploy, build, startup, CI/CD, Dockerfile, entrypoint, dan post-deploy tidak boleh mengandung database reset.

## Validasi dan performa

Uji secara proporsional terhadap risiko perubahan. Prioritaskan test untuk:

- business logic;
- permission dan access control;
- calculation;
- data mutation;
- database migration;
- shared behavior; dan
- bug regression.

Jangan mengklaim sesuatu sudah tervalidasi jika belum benar-benar dijalankan atau diperiksa.

Optimasi hanya jika relevan terhadap task atau bottleneck. Prioritasnya:

1. Hilangkan pekerjaan, query, request, atau render yang tidak diperlukan.
2. Optimalkan query dan hindari N+1.
3. Gunakan pagination untuk dataset besar.
4. Minimalkan API payload dan deduplicate request.
5. Gunakan caching bila data sering dibaca dan invalidasinya jelas.
6. Lazy-load komponen atau media berat dan optimalkan image.
7. Tambah database index berdasarkan access pattern nyata.
8. Gunakan code splitting, memoization, CDN, atau background job bila memang dibutuhkan.

Jangan mengorbankan simplicity demi micro-optimization.

## Klarifikasi dan konteks proyek

Minta klarifikasi hanya jika ambiguitas dapat mengubah hasil secara material, seperti business rule, permission, destructive operation, data interpretation, scope, atau behavior yang memiliki beberapa pilihan valid. Tentukan detail lain dari codebase, test, dokumentasi, design system, atau convention existing.

- Perubahan product atau business rule: baca `PRODUCT.md` dan `docs/PRD.md`.
- Architecture, dependency, integration, data, atau deployment: baca `docs/TECH-STACK.md` dan `docs/ARCHITECTURE.md`.
- UI/UX: baca `DESIGN.md`.
- Database: baca schema, migration, dan deployment documentation yang relevan.

Jangan membaca seluruh dokumentasi tanpa alasan.

Jika Graphify, Impeccable, test, build, atau tool wajib gagal:

1. Laporkan perintah dan error yang relevan.
2. Jangan mengklaim proses berhasil.
3. Jangan membuat pengganti atau hasil tiruan.
4. Jangan memasang tool lain tanpa kebutuhan atau persetujuan.

Jika dua aturan bertentangan, gunakan urutan:

```text
1. Keselamatan data
2. Security dan permission
3. Correctness
4. Scope / acceptance criteria
5. Existing behavior
6. Existing architecture
7. Regression prevention
8. Testing
9. Performance
10. Cleanup / improvement
```

## Prinsip akhir

```text
Understand
→ Trace
→ Change minimally
→ Protect data
→ Test
→ Validate
→ Stop
```
