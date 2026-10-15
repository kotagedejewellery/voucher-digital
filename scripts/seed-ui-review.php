<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.pgsql.database')!=='voucher_test' || !app()->environment('local')) { throw new RuntimeException('Hanya untuk database test dan preview lokal.'); }
if (config('database.connections.pgsql.host')!=='test-db' || Illuminate\Support\Facades\DB::selectOne('select current_database() as name')->name!=='voucher_test') { throw new RuntimeException('Host/database aktual bukan target fixture yang diizinkan.'); }
Illuminate\Support\Facades\Artisan::call('migrate:fresh',['--seed'=>true,'--force'=>true]);
App\Models\User::create(['name'=>getenv('LOCAL_ADMIN_NAME'),'email'=>getenv('LOCAL_ADMIN_EMAIL'),'password'=>getenv('LOCAL_ADMIN_PASSWORD')]);
App\Models\Branch::create(['name'=>'Cabang fixture UI','address'=>'Data sintetis untuk pengujian; bukan alamat cabang KGJ.']);
App\Models\User::create(['name'=>'Operator fixture tambahan','email'=>'operator.fixture@kgj.test','password'=>bin2hex(random_bytes(16))]);
echo "Fixture UI siap pada database test; tidak ada QRIS pembayaran nyata.\n";
