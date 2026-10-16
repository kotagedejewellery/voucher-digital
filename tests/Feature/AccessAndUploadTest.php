<?php
namespace Tests\Feature;
use App\Models\{Branch,Delivery,Export,PaymentOrder,QrisAsset,Redemption,User,Voucher,VoucherPolicy};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccessAndUploadTest extends TestCase
{
    use RefreshDatabase;
    public function test_upload_rejects_svg_and_guest_and_export_is_private(): void
    {
        $admin=User::factory()->create();
        $data=['qris'=>UploadedFile::fake()->createWithContent('merchant.svg','<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'merchant_name'=>'Test','receiver_key'=>'TEST','merchant_checked'=>true];
        $this->post('/admin/pengaturan/qris',$data)->assertRedirect('/admin/login');
        $this->actingAs($admin)->postJson('/admin/pengaturan/qris',$data)->assertUnprocessable()->assertJsonValidationErrors('qris');
        Storage::fake('local'); Storage::disk('local')->put('exports/test.csv','test');
        $export=Export::create(['user_id'=>$admin->id,'filters'=>[],'status'=>'READY','path'=>'exports/test.csv','expires_at'=>now()->addDay()]);
        $this->get('/admin/exports/'.$export->id)->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->actingAs(User::factory()->create())->get('/admin/exports/'.$export->id)->assertForbidden();
        $export->update(['expires_at'=>now()->subMinute(),'status'=>'FAILED']);
        $this->artisan('voucher:prune')->assertSuccessful();
        $this->assertFalse(Storage::disk('local')->exists('exports/test.csv'));
    }
    public function test_customer_post_without_csrf_is_rejected_outside_test_bypass(): void
    {
        $this->app['env']='production';
        $this->postJson('/checkout',['name'=>'Test','phone'=>'081234567890','service_consent'=>true])->assertStatus(419);
    }
    public function test_expired_payment_form_recovers_to_its_status_without_external_redirects(): void
    {
        $this->withoutVite();
        $this->app['env']='production';
        $token=str_repeat('a',64);
        $this->post('/pesanan/'.$token.'/confirm',['return_to'=>'https://untrusted.example/'])
            ->assertStatus(419)->assertSee('Periksa status pesanan')->assertSee(route('payment.show',$token))
            ->assertDontSee('https://untrusted.example/')->assertHeader('Cache-Control','no-store, private');
    }

    public function test_named_operator_can_open_the_admin_surfaces(): void
    {
        $this->withoutVite(); $this->seed(); $this->actingAs(User::factory()->create());
        foreach (['/admin','/admin/pesanan','/admin/riwayat-voucher','/admin/pengaturan'] as $url) { $this->get($url)->assertOk(); }
    }

    public function test_voucher_history_filters_lifecycle_and_shows_claim_attention(): void
    {
        $this->withoutVite(); $this->seed(); $admin=User::factory()->create();
        $policy=VoucherPolicy::current(); $branch=Branch::create(['name'=>'Cabang Riwayat','address'=>'Alamat fixture']);
        $qris=QrisAsset::create(['path'=>'qris/history.png','mime'=>'image/png','merchant_name'=>'KGJ','receiver_key'=>'KGJ-HISTORY','uploaded_by'=>$admin->id]);
        $makeVoucher=function (string $reference,string $code,int $daysAgo) use ($admin,$policy,$qris) {
            $order=PaymentOrder::create(['reference'=>$reference,'token_hash'=>hash('sha256',$reference),'access_token'=>str_repeat('a',64),'marketing_consent'=>false,'service_consented_at'=>now(),'policy_id'=>$policy->id,'qris_asset_id'=>$qris->id,'price'=>100000,'receiver_key'=>'KGJ-HISTORY','status'=>'PAID','paid_at'=>now()->subDays($daysAgo),'verified_at'=>now(),'verified_by'=>$admin->id]);
            return Voucher::create(['payment_order_id'=>$order->id,'code'=>$code,'token_hash'=>hash('sha256',$code),'access_token'=>str_repeat('b',64),'marketing_consent'=>false]);
        };
        $active=$makeVoucher('VS-HISTORY-ACTIVE','HISTORYACT',5);
        $used=$makeVoucher('VS-HISTORY-USED','HISTORYUSED',10); $used->update(['status'=>'TERPAKAI']);
        Redemption::create(['voucher_id'=>$used->id,'branch_id'=>$branch->id,'order_ref'=>'ORDER-HISTORY','actor_id'=>$admin->id,'request_key'=>fake()->uuid(),'request_digest'=>hash('sha256','used'),'discount'=>800000,'age_days'=>10,'claimed_at'=>now()]);
        Delivery::create(['voucher_id'=>$used->id,'kind'=>'WhatsApp','event_id'=>fake()->uuid(),'revision'=>1,'status'=>'FAILED','last_error'=>'Fixture failure']);
        $expired=$makeVoucher('VS-HISTORY-EXPIRED','HISTORYEX',366);

        $this->actingAs($admin)->get('/admin/riwayat-voucher')->assertOk()->assertSee($active->code)->assertSee($used->code)->assertSee($expired->code)->assertSee('Pengiriman perlu diperiksa');
        $this->actingAs($admin)->get('/admin/riwayat-voucher?lifecycle=TERPAKAI')->assertOk()->assertSee($used->code)->assertDontSee($active->code)->assertDontSee($expired->code);
        $this->actingAs($admin)->get('/admin/riwayat-voucher?lifecycle=KEDALUWARSA')->assertOk()->assertSee($expired->code)->assertDontSee($active->code)->assertDontSee($used->code);
    }

    public function test_landing_can_be_read_repeatedly_from_the_database_cache(): void
    {
        $this->withoutVite(); $this->seed(); config(['cache.default'=>'database']);
        \App\Models\Branch::create(['name'=>'Cabang fixture cache','address'=>'Alamat sintetis']);
        $this->get('/')->assertOk()->assertSee('Cabang fixture cache');
        $this->get('/')->assertOk()->assertSee('Cabang fixture cache');
    }
}
