<?php
namespace Tests\Feature;
use App\Models\{Export,User};
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
        foreach (['/admin','/admin/pesanan','/admin/pengaturan'] as $url) { $this->get($url)->assertOk(); }
    }

    public function test_landing_can_be_read_repeatedly_from_the_database_cache(): void
    {
        $this->withoutVite(); $this->seed(); config(['cache.default'=>'database']);
        \App\Models\Branch::create(['name'=>'Cabang fixture cache','address'=>'Alamat sintetis']);
        $this->get('/')->assertOk()->assertSee('Cabang fixture cache');
        $this->get('/')->assertOk()->assertSee('Cabang fixture cache');
    }
}
