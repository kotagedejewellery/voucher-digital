<?php
namespace Tests\Feature;

use App\Models\QrisAsset;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\VoucherPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $admin=User::factory()->create();
        $policy=VoucherPolicy::create(['price'=>100000,'minimum_order'=>8000000,'valid_days'=>365,'tiers'=>[
            ['end'=>30,'discount'=>800000],['end'=>60,'discount'=>500000],['end'=>90,'discount'=>300000],['end'=>365,'discount'=>100000],
        ]]);
        $qris=QrisAsset::create(['path'=>'qris/test-only.png','mime'=>'image/png','merchant_name'=>'Merchant test','receiver_key'=>'TEST-MERCHANT','uploaded_by'=>$admin->id]);
        Setting::put('policy_id',(string)$policy->id);
        Setting::put('qris_id',(string)$qris->id);
    }

    public function test_checkout_validation_uses_readable_indonesian_feedback(): void
    {
        $this->postJson('/checkout',['name'=>'','phone'=>'081234567890','service_consent'=>true])
            ->assertUnprocessable()->assertJsonPath('errors.name.0','Nama lengkap wajib diisi.');
    }

    public function test_voucher_explains_next_discount_date_from_its_payment_snapshot(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-30 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test informasi tier','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-NEXT-TIER','paid_at'=>'2026-01-01T23:59:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        foreach (['2026-01-30'=>['31/01/2026','Rp500.000'],'2026-03-01'=>['02/03/2026','Rp300.000'],'2026-03-31'=>['01/04/2026','Rp100.000']] as $today=>$expected) {
            $this->travelTo(\Carbon\CarbonImmutable::parse($today.' 12:00:00','Asia/Jakarta'));
            $this->get($approval->json('voucher_url'))->assertOk()->assertSee('Mulai '.$expected[0])->assertSee($expected[1]);
        }
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-04-01 12:00:00','Asia/Jakarta'));
        $this->get($approval->json('voucher_url'))->assertOk()->assertDontSee('Potongan berikutnya')->assertSee('31/12/2026');
    }

    public function test_customer_confirmation_does_not_issue_a_voucher(): void
    {
        $checkout=$this->withServerVariables(['HTTP_HOST'=>'untrusted.example'])->postJson('/checkout',[
            'name'=>'Pelanggan test','phone'=>'081234567890','service_consent'=>true,'marketing_consent'=>false,
        ])->assertCreated();
        $url=$checkout->json('payment_url');
        $this->assertStringStartsWith(config('app.url').'/pesanan/',$url);
        $this->postJson($url.'/confirm',['note'=>'Sudah membayar'])->assertOk()->assertJsonPath('status','MENUNGGU_VERIFIKASI');
        $this->getJson($url)->assertOk()->assertJsonPath('voucher_url',null);
        $this->getJson('/pesanan/token-tidak-valid')->assertNotFound();
    }

    public function test_pending_verification_queue_prioritizes_the_first_customer_confirmation(): void
    {
        $first=$this->postJson('/checkout',['name'=>'Pelanggan pertama','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->travel(1)->minutes();
        $second=$this->postJson('/checkout',['name'=>'Pelanggan kedua','phone'=>'081234567891','service_consent'=>true])->assertCreated();

        $this->postJson($first->json('payment_url').'/confirm')->assertOk();
        $this->travel(1)->minutes();
        $this->postJson($second->json('payment_url').'/confirm')->assertOk();

        $firstOrder=PaymentOrder::where('reference',$first->json('reference'))->firstOrFail();
        $secondOrder=PaymentOrder::where('reference',$second->json('reference'))->firstOrFail();
        $this->assertNotNull($firstOrder->customer_confirmed_at);
        $this->assertNotNull($secondOrder->customer_confirmed_at);

        $page=$this->actingAs(User::first())->get('/admin/pesanan')->assertOk();
        $this->assertLessThan(
            strpos($page->getContent(),'<strong class="order-ref">'.$secondOrder->reference),
            strpos($page->getContent(),'<strong class="order-ref">'.$firstOrder->reference),
        );
    }

    public function test_payment_page_explains_when_the_qris_image_is_unavailable(): void
    {
        $checkout=$this->postJson('/checkout',['name'=>'Pelanggan QRIS','phone'=>'081234567890','service_consent'=>true])->assertCreated();

        $this->get($checkout->json('payment_url'))
            ->assertOk()
            ->assertSee('QRIS belum dapat ditampilkan.')
            ->assertSee('Saya sudah membayar Rp100.000');
    }

    public function test_payment_page_groups_qris_merchant_nominal_and_next_steps(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('qris/test-only.png','test-image');
        $checkout=$this->postJson('/checkout',['name'=>'Pelanggan QRIS','phone'=>'081234567890','service_consent'=>true])->assertCreated();

        $this->get($checkout->json('payment_url'))
            ->assertOk()
            ->assertSeeInOrder(['Merchant test','Rp100.000','Pindai QRIS','Saya sudah membayar Rp100.000'])
            ->assertDontSee('QRIS belum dapat ditampilkan.');
    }

    public function test_verified_money_issues_one_voucher_using_actual_payment_date(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Pelanggan test','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $reference=$checkout->json('reference');
        $data=['merchant_ref'=>'MUTASI-TEST-001','paid_at'=>'2026-09-14T12:00:00','amount'=>100000,'money_confirmed'=>true];
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$reference.'/approve',$data)->assertOk();
        $again=$this->postJson('/admin/pesanan/'.$reference.'/approve',$data)->assertOk();
        $this->assertSame($approval->json('code'),$again->json('code'));
        $this->getJson($approval->json('voucher_url'))->assertOk()->assertJsonPath('age',31)->assertJsonPath('discount',500000);
        $this->postJson($checkout->json('payment_url').'/confirm')->assertOk()->assertJsonPath('status','PAID');
        $second=$this->postJson('/checkout',['name'=>'Pelanggan kedua','phone'=>'081234567891','service_consent'=>true])->assertCreated();
        $this->postJson('/admin/pesanan/'.$second->json('reference').'/approve',$data)->assertUnprocessable();
        $this->getJson($second->json('payment_url'))->assertJsonPath('voucher_url',null);
    }

    public function test_admin_can_see_campaign_progress_and_the_operator_audit_for_an_order(): void
    {
        $checkout=$this->postJson('/checkout',['name'=>'Pelanggan campaign','phone'=>'081234567890','service_consent'=>true,'utm_campaign'=>'IG-Oct'])->assertCreated();
        $admin=User::first();
        $this->actingAs($admin)->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'CAMPAIGN-IG-OCT','paid_at'=>now('Asia/Jakarta')->format('Y-m-d\\TH:i:s'),'amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Kinerja campaign')->assertSee('IG-Oct');
        $this->actingAs($admin)->get('/admin/pesanan/'.$checkout->json('reference'))->assertOk()
            ->assertSee('Riwayat tindakan operator')->assertSee('Pembayaran diverifikasi');
    }

    public function test_claim_locks_discount_once_and_retry_recovers_the_same_result(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test klaim','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-CLAIM','paid_at'=>'2026-10-14T11:00:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        $preview=$this->getJson($approval->json('voucher_url'))->assertJsonPath('status','AKTIF');
        $branch=\App\Models\Branch::create(['name'=>'Cabang test','address'=>'Data sintetis untuk test']);
        $data=['branch_id'=>$branch->id,'order_ref'=>'  order-test-001  ','quote'=>$preview->json('quote'),
            'request_key'=>(string)\Illuminate\Support\Str::uuid(),'eligibility_confirmed'=>true,'order_confirmed'=>true];
        $url='/admin/vouchers/'.$approval->json('code').'/claim';
        $claim=$this->postJson($url,$data)->assertOk()->assertJsonPath('discount',800000)->assertJsonPath('order_ref','ORDER-TEST-001');
        $again=$this->postJson($url,$data)->assertOk();
        $this->assertSame($claim->json('claim_id'),$again->json('claim_id'));
        $this->postJson($url,[...$data,'order_ref'=>'ORDER-LAIN'])->assertConflict();
        $this->postJson($url,[...$data,'request_key'=>(string)\Illuminate\Support\Str::uuid()])->assertUnprocessable();
        $this->getJson($approval->json('voucher_url'))->assertJsonPath('status','TERPAKAI');
    }

    public function test_transfer_revokes_old_token_code_and_order_access_to_the_new_owner(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Pemilik lama','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-TRANSFER','paid_at'=>'2026-10-14T11:00:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        $transfer=$this->postJson('/admin/vouchers/'.$approval->json('code').'/transfer',[
            'name'=>'Penerima test','phone'=>'081234567891','owner_approved'=>true,'approval_note'=>'Persetujuan WA terdaftar telah diperiksa',
        ])->assertOk();
        $this->assertNotSame($approval->json('code'),$transfer->json('code'));
        $this->getJson($approval->json('voucher_url'))->assertNotFound();
        $this->getJson($checkout->json('payment_url'))->assertJsonPath('voucher_url',null);
        $this->getJson($transfer->json('voucher_url'))->assertJsonPath('age',1);
        $this->postJson('/admin/vouchers/'.$transfer->json('code').'/transfer',[
            'name'=>'Penerima lain','phone'=>'081234567892','owner_approved'=>true,'approval_note'=>'Test kedua',
        ])->assertUnprocessable();
    }

    public function test_all_discount_boundaries_use_calendar_dates_in_wib(): void
    {
        $paid=\Carbon\CarbonImmutable::parse('2026-01-01 23:59:00','Asia/Jakarta');
        $this->travelTo($paid->addMinute());
        $checkout=$this->postJson('/checkout',['name'=>'Test batas hari','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-TIERS','paid_at'=>'2026-01-01T23:59:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        foreach ([[1,800000],[30,800000],[31,500000],[60,500000],[61,300000],[90,300000],[91,100000],[365,100000],[366,0]] as [$day,$discount]) {
            $this->travelTo($paid->startOfDay()->addDays($day-1)->addHours(23));
            $this->getJson($approval->json('voucher_url'))->assertOk()->assertJsonPath('age',$day)->assertJsonPath('discount',$discount);
        }
    }

    public function test_changed_quote_does_not_consume_the_voucher(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-30 23:59:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test pergantian hari','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-MIDNIGHT','paid_at'=>'2026-01-01T10:00:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        $quote=$this->getJson($approval->json('voucher_url'))->assertJsonPath('discount',800000)->json('quote');
        $branch=\App\Models\Branch::create(['name'=>'Cabang test','address'=>'Data sintetis']);
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-31 00:00:00','Asia/Jakarta'));
        $this->postJson('/admin/vouchers/'.$approval->json('code').'/claim',[
            'branch_id'=>$branch->id,'order_ref'=>'MIDNIGHT','quote'=>$quote,'request_key'=>(string)\Illuminate\Support\Str::uuid(),'eligibility_confirmed'=>true,'order_confirmed'=>true,
        ])->assertUnprocessable()->assertJsonValidationErrors('quote');
        $this->getJson($approval->json('voucher_url'))->assertJsonPath('status','AKTIF')->assertJsonPath('discount',500000);
    }

    public function test_approval_rejects_future_time_wrong_amount_and_missing_confirmation(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test validasi','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $url='/admin/pesanan/'.$checkout->json('reference').'/approve';
        $data=['merchant_ref'=>'TEST-INVALID','paid_at'=>'2026-10-14T11:00:00','amount'=>100000,'money_confirmed'=>true];
        $this->postJson($url,[...$data,'paid_at'=>'2026-10-15T12:00:00'])->assertUnprocessable()->assertJsonValidationErrors('paid_at');
        $this->postJson($url,[...$data,'amount'=>99999])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson($url,[...$data,'money_confirmed'=>false])->assertUnprocessable()->assertJsonValidationErrors('money_confirmed');
        $this->getJson($checkout->json('payment_url'))->assertJsonPath('status','MENUNGGU_PEMBAYARAN')->assertJsonPath('voucher_url',null);
    }

    public function test_approval_page_uses_server_time_validation_and_recovers_duplicate_references(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $first=$this->postJson('/checkout',['name'=>'Pembayaran pertama','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $second=$this->postJson('/checkout',['name'=>'Pembayaran kedua','phone'=>'081234567891','service_consent'=>true])->assertCreated();
        $data=['merchant_ref'=>'MUTASI-DUPLIKAT','paid_at'=>'2026-10-14T11:00:00','amount'=>100000,'money_confirmed'=>true];
        $this->actingAs(User::first());
        $this->postJson('/admin/pesanan/'.$first->json('reference').'/approve',$data)->assertOk();
        $page='/admin/pesanan/'.$second->json('reference');
        $this->get($page)->assertOk()->assertSee('id="paid_at"',false)->assertDontSee('max="',false);
        $this->from($page)->post('/admin/pesanan/'.$second->json('reference').'/approve',$data)->assertRedirect($page);
        $this->get($page)->assertOk()
            ->assertSee('Cari referensi ini di semua status pesanan')
            ->assertSee('?search=MUTASI-DUPLIKAT&amp;status=',false);
    }

    public function test_failed_queue_insert_rolls_back_payment_and_voucher(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test rollback','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        \Illuminate\Support\Facades\DB::unprepared("CREATE FUNCTION test_reject_queue() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'test queue unavailable'; END; $$; CREATE TRIGGER test_reject_queue BEFORE INSERT ON jobs FOR EACH ROW EXECUTE FUNCTION test_reject_queue();");
        $this->actingAs(User::first());
        $this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-ROLLBACK','paid_at'=>'2026-10-14T11:00:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertStatus(500);
        $this->getJson($checkout->json('payment_url'))->assertJsonPath('status','MENUNGGU_PEMBAYARAN')->assertJsonPath('voucher_url',null);
        \Illuminate\Support\Facades\DB::unprepared('DROP TRIGGER test_reject_queue ON jobs; DROP FUNCTION test_reject_queue();');
    }

    public function test_new_settings_keep_the_original_payment_image_and_price(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $old=$this->postJson('/checkout',['name'=>'Pesanan lama','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $this->post('/admin/pengaturan/policy',['price'=>120000,'minimum_order'=>8000000,'discount_30'=>800000,
            'discount_60'=>500000,'discount_90'=>300000,'discount_365'=>100000,'policy_confirmed'=>true])->assertRedirect();
        $chunk=fn($type,$data)=>pack('N',strlen($data)).$type.$data.pack('N',crc32($type.$data));
        // A plain white PNG fixture contains no payment QR payload.
        $png="\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',200,200,8,2,0,0,0)).$chunk('IDAT',gzcompress(str_repeat("\0".str_repeat("\xff",600),200))).$chunk('IEND','');
        $upload=\Illuminate\Http\UploadedFile::fake()->createWithContent('non-payable-test.png',$png);
        $this->post('/admin/pengaturan/qris',['qris'=>$upload,'merchant_name'=>'Merchant fixture baru (bukan pembayaran)','receiver_key'=>'TEST-NEW','merchant_checked'=>true])->assertRedirect();
        $new=$this->postJson('/checkout',['name'=>'Pesanan baru','phone'=>'081234567891','service_consent'=>true])->assertCreated();
        $this->getJson($old->json('payment_url'))->assertJsonPath('price',100000);
        $this->getJson($new->json('payment_url'))->assertJsonPath('price',120000);
        $oldHtml=$this->get($old->json('payment_url'))->assertOk()->assertSee('Merchant test');
        $newHtml=$this->get($new->json('payment_url'))->assertOk()->assertSee('Merchant fixture baru');
        $id=Setting::read('qris_id');
        $download=$this->get('/qris/'.$id.'?download=1')->assertOk()->assertHeader('Content-Type','image/png');
        $this->assertSame($png,$download->streamedContent());
        $this->post($old->json('payment_url').'/confirm')->assertRedirect($old->json('payment_url'));
    }

    public function test_retention_scrubs_owner_data_but_preserves_the_financial_reference(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-01 12:00:00','Asia/Jakarta'));
        $checkout=$this->postJson('/checkout',['name'=>'Test retensi','phone'=>'081234567890','service_consent'=>true])->assertCreated();
        $this->actingAs(User::first());
        $approval=$this->postJson('/admin/pesanan/'.$checkout->json('reference').'/approve',[
            'merchant_ref'=>'TEST-RETENTION','paid_at'=>'2026-01-01T11:00:00','amount'=>100000,'money_confirmed'=>true,
        ])->assertOk();
        $branch=\App\Models\Branch::create(['name'=>'Cabang fixture','address'=>'Data sintetis']);
        $quote=$this->getJson($approval->json('voucher_url'))->json('quote');
        $this->postJson('/admin/vouchers/'.$approval->json('code').'/claim',[
            'branch_id'=>$branch->id,'order_ref'=>'RETENTION','quote'=>$quote,'request_key'=>(string)\Illuminate\Support\Str::uuid(),'eligibility_confirmed'=>true,'order_confirmed'=>true,
        ])->assertOk();
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-04-02 12:00:00','Asia/Jakarta'));
        $this->post('/admin/pesanan/'.$checkout->json('reference').'/support',['support_open'=>true])->assertRedirect();
        $this->artisan('voucher:prune')->assertSuccessful();
        $this->getJson($approval->json('voucher_url'))->assertOk();
        $this->post('/admin/pesanan/'.$checkout->json('reference').'/support',['support_open'=>false])->assertRedirect();
        $this->artisan('voucher:prune')->assertSuccessful();
        $this->getJson($approval->json('voucher_url'))->assertNotFound();
        $this->getJson($checkout->json('payment_url'))->assertNotFound();
        $this->get('/admin/pesanan/'.$checkout->json('reference'))->assertOk()->assertDontSee('Test retensi')->assertSee('TEST-RETENTION');
    }
}
