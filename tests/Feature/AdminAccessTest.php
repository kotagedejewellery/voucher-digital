<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;
    public function test_customer_cannot_open_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/vouchers/1/resend')->assertRedirect('/admin/login');
        $this->post('/admin/deliveries/1/retry')->assertRedirect('/admin/login');
    }

    public function test_disabled_operator_cannot_access_admin_or_livewire(): void
    {
        $user=User::factory()->create(); $user->active=false; $user->save();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/pesanan')->assertForbidden();
        $this->actingAs($user)->post('/admin/vouchers/1/resend')->assertForbidden();
        $this->actingAs($user)->post('/admin/deliveries/1/retry')->assertForbidden();
        $this->actingAs($user)->get('/admin/pesanan/UNKNOWN')->assertForbidden();
    }

    public function test_login_is_rate_limited_and_registration_is_absent(): void
    {
        for ($i=0;$i<6;$i++) { $this->postJson('/admin/login',['email'=>'unknown@example.test','password'=>'wrong'])->assertUnprocessable(); }
        $this->postJson('/admin/login',['email'=>'unknown@example.test','password'=>'wrong'])->assertTooManyRequests();
        $this->get('/register')->assertNotFound();
    }

    public function test_logout_ends_the_session_and_returns_feedback(): void
    {
        $user=User::factory()->create();
        $this->withoutVite();
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('data-confirm-title="Keluar dari panel?"',false);
        $this->post('/admin/logout')->assertRedirect('/admin/login')
            ->assertSessionHas('success','Anda sudah keluar dari panel.')
            ->assertSessionHas('feedback','toast');
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_lookup_keeps_manual_and_camera_voucher_entry(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk()
            ->assertSee('id="code"', false)
            ->assertSee('data-start-scanner', false)
            ->assertSee('data-scan-preview', false)
            ->assertSee('aria-label="Pratinjau kamera pemindai voucher"', false);
    }

    public function test_deactivated_operator_loses_access_on_the_next_request(): void
    {
        $admin=User::factory()->create(); $operator=User::factory()->create();
        $this->actingAs($admin)->post('/admin/pengaturan/users/'.$operator->id,['active'=>false])->assertRedirect();
        $this->actingAs($operator)->get('/admin')->assertForbidden();
    }
}
