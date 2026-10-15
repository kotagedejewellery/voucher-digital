<?php
namespace Tests\Feature;

use App\Models\{Branch,QrisAsset,Setting,User,VoucherPolicy};
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcurrentVoucherTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_database_connections_issue_once_and_only_one_operator_can_claim(): void
    {
        $this->withoutVite(); $this->seed();
        $users=[User::factory()->create(),User::factory()->create()];
        $qris=QrisAsset::create(['path'=>'qris/test-only.png','mime'=>'image/png','merchant_name'=>'Fixture merchant','receiver_key'=>'TEST','uploaded_by'=>$users[0]->id]);
        Setting::put('qris_id',(string)$qris->id);
        $checkout=$this->postJson('/checkout',['name'=>'Test concurrency','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $approvalData=['merchant_ref'=>'TEST-RACE','paid_at'=>now('Asia/Jakarta')->subMinute()->format('Y-m-d\TH:i:s'),'amount'=>100000,'money_confirmed'=>true];
        $approvals=$this->concurrently(function ($i) use ($users,$checkout,$approvalData) {
            $this->actingAs(User::findOrFail($users[$i]->id));
            return $this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',$approvalData);
        });
        $this->assertSame([200,200],array_column($approvals,'status'));
        $this->assertSame($approvals[0]['body']['code'],$approvals[1]['body']['code']);
        $voucherUrl=$approvals[0]['body']['voucher_url']; $code=$approvals[0]['body']['code'];
        $preview=$this->getJson($voucherUrl)->assertOk();
        $branch=Branch::create(['name'=>'Cabang fixture','address'=>'Data sintetis']);
        $claimData=['branch_id'=>$branch->id,'order_ref'=>'RACE-ORDER','quote'=>$preview->json('quote'),'eligibility_confirmed'=>true,'order_confirmed'=>true];
        $claims=$this->concurrently(function ($i) use ($users,$code,$claimData) {
            $this->actingAs(User::findOrFail($users[$i]->id));
            return $this->postJson('/admin/vouchers/'.$code.'/claim',[...$claimData,'request_key'=>(string)Str::uuid()]);
        });
        $statuses=array_column($claims,'status'); sort($statuses);
        $this->assertSame([200,422],$statuses);
        $this->getJson($voucherUrl)->assertJsonPath('status','TERPAKAI');
        $this->actingAs($users[0]);
        $details=$this->getJson('/admin/pesanan/'.$checkout->json('reference'))->assertOk()->json('deliveries');
        $this->assertCount(1,array_filter($details,fn($d)=>$d['kind']==='Purchase'));
        $this->assertCount(1,array_filter($details,fn($d)=>$d['kind']==='VoucherRedeemed'));

        $second=$this->postJson('/checkout',['name'=>'Test transfer race','phone'=>'081234567891','service_consent'=>true])->assertCreated();
        $secondApproval=$this->postJson('/admin/pesanan/'.$second->json('reference').'/approve',[...$approvalData,'merchant_ref'=>'TEST-RACE-TRANSFER'])->assertOk();
        $secondCode=$secondApproval->json('code');
        $secondQuote=$this->getJson($secondApproval->json('voucher_url'))->json('quote');
        $this->postJson('/admin/vouchers/'.$secondCode.'/claim',[...$claimData,'quote'=>$secondQuote,'request_key'=>(string)Str::uuid()])->assertUnprocessable();
        $this->getJson($secondApproval->json('voucher_url'))->assertJsonPath('status','AKTIF');
        $race=$this->concurrently(function ($i) use ($users,$secondCode,$secondQuote,$branch) {
            $this->actingAs(User::findOrFail($users[$i]->id));
            return $i===0 ? $this->postJson('/admin/vouchers/'.$secondCode.'/transfer',[
                'name'=>'Penerima fixture','phone'=>'081234567892','owner_approved'=>true,'approval_note'=>'Persetujuan fixture',
            ]) : $this->postJson('/admin/vouchers/'.$secondCode.'/claim',[
                'branch_id'=>$branch->id,'order_ref'=>'RACE-TRANSFER','quote'=>$secondQuote,'eligibility_confirmed'=>true,'order_confirmed'=>true,'request_key'=>(string)Str::uuid(),
            ]);
        });
        $successes=array_filter($race,fn($r)=>$r['status']===200);
        $this->assertCount(1,$successes,json_encode(array_column($race,'status')));
        foreach ($race as $result) { $this->assertContains($result['status'],[200,404,422]); }
        if ($race[0]['status']===200) {
            $this->getJson($race[0]['body']['voucher_url'])->assertOk()->assertJsonPath('status','AKTIF')->assertJsonPath('code',$race[0]['body']['code']);
            $this->getJson($secondApproval->json('voucher_url'))->assertNotFound();
        } else {
            $this->getJson($secondApproval->json('voucher_url'))->assertOk()->assertJsonPath('status','TERPAKAI')->assertJsonPath('code',$secondCode);
            $this->get('/admin/voucher?code='.$secondCode)->assertOk()->assertSee('RACE-TRANSFER');
        }
    }

    private function concurrently(\Closure $request): array
    {
        $dir=sys_get_temp_dir().'/voucher-race-'.Str::uuid(); mkdir($dir);
        // Keep the connection object shared by Eloquent and the database queue; replace only its PDO after fork.
        DB::disconnect(); $pids=[];
        for ($i=0;$i<2;$i++) {
            $pid=pcntl_fork();
            if ($pid===-1) { throw new \RuntimeException('fork gagal'); }
            if ($pid===0) {
                try {
                    DB::reconnect(); file_put_contents($dir.'/ready-'.$i,'1');
                    $deadline=microtime(true)+10;
                    while (!file_exists($dir.'/start')) { if (microtime(true)>$deadline) { throw new \RuntimeException('barrier timeout'); } usleep(1000); }
                    $response=$request($i);
                    file_put_contents($dir.'/result-'.$i,json_encode(['status'=>$response->status(),'body'=>$response->json()],JSON_THROW_ON_ERROR));
                    exit(0);
                } catch (\Throwable $e) { file_put_contents($dir.'/result-'.$i,json_encode(['status'=>0,'error'=>$e->getMessage()])); exit(1); }
            }
            $pids[]=$pid;
        }
        $deadline=microtime(true)+10;
        while (!file_exists($dir.'/ready-0') || !file_exists($dir.'/ready-1')) { if (microtime(true)>$deadline) { throw new \RuntimeException('child timeout'); } usleep(1000); }
        file_put_contents($dir.'/start','1');
        foreach ($pids as $pid) { pcntl_waitpid($pid,$status); }
        DB::reconnect();
        $results=[];
        for ($i=0;$i<2;$i++) { $results[]=json_decode(file_get_contents($dir.'/result-'.$i),true,flags:JSON_THROW_ON_ERROR); }
        foreach (glob($dir.'/*') as $file) { unlink($file); } rmdir($dir);
        return $results;
    }
}
