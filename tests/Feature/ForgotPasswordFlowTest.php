<?php

namespace Tests\Feature;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email'    => 'waka@smkn1bantul.sch.id',
            'username' => 'waka_test',
            'password' => Hash::make('oldpassword123'),
            'role_id'  => 1,
        ]);
    }

    public function test_can_view_forgot_password_email_form(): void
    {
        $response = $this->get(route('password.forgot'));
        $response->assertStatus(200);
        $response->assertSee('Lupa Password');
    }

    public function test_send_otp_fails_for_unregistered_email(): void
    {
        $response = $this->from(route('password.forgot'))
            ->post(route('password.send-otp'), [
                'email' => 'unknown@domain.com',
            ]);

        $response->assertRedirect(route('password.forgot'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_send_otp_success_creates_otp_and_redirects_to_verify_form(): void
    {
        Mail::fake();

        $response = $this->post(route('password.send-otp'), [
            'email' => 'waka@smkn1bantul.sch.id',
        ]);

        $response->assertRedirect(route('password.verify.form'));
        $response->assertSessionHas('otp_email', 'waka@smkn1bantul.sch.id');
        $response->assertSessionHas('otp_verified', false);

        $this->assertDatabaseHas('password_otps', [
            'email' => 'waka@smkn1bantul.sch.id',
        ]);
    }

    public function test_cannot_access_verify_form_without_email_session(): void
    {
        $response = $this->get(route('password.verify.form'));
        $response->assertRedirect(route('password.forgot'));
    }

    public function test_cannot_verify_wrong_otp(): void
    {
        // Buat record OTP
        PasswordOtp::create([
            'email'      => 'waka@smkn1bantul.sch.id',
            'otp_hash'   => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession(['otp_email' => 'waka@smkn1bantul.sch.id'])
            ->from(route('password.verify.form'))
            ->post(route('password.verify.post'), [
                'otp' => '999999',
            ]);

        $response->assertRedirect(route('password.verify.form'));
        $response->assertSessionHasErrors(['otp']);

        $otpRecord = PasswordOtp::where('email', 'waka@smkn1bantul.sch.id')->first();
        $this->assertEquals(1, $otpRecord->attempts);
    }

    public function test_can_verify_correct_otp_even_with_whitespace_and_redirects_to_reset_form(): void
    {
        PasswordOtp::create([
            'email'      => 'waka@smkn1bantul.sch.id',
            'otp_hash'   => Hash::make('654321'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession(['otp_email' => 'waka@smkn1bantul.sch.id'])
            ->post(route('password.verify.post'), [
                'otp' => ' 654 321 ', // tes input dengan spasi
            ]);

        $response->assertRedirect(route('password.reset.form'));
        $response->assertSessionHas('otp_verified', true);
    }

    public function test_cannot_access_reset_form_without_otp_verified_session(): void
    {
        // Hanya punya session email tapi belum verifikasi OTP
        $response = $this->withSession(['otp_email' => 'waka@smkn1bantul.sch.id', 'otp_verified' => false])
            ->get(route('password.reset.form'));

        $response->assertRedirect(route('password.forgot'));
    }

    public function test_can_access_reset_form_when_otp_verified(): void
    {
        $response = $this->withSession([
            'otp_email'    => 'waka@smkn1bantul.sch.id',
            'otp_verified' => true,
        ])->get(route('password.reset.form'));

        $response->assertStatus(200);
        $response->assertSee('Perbarui Password');
        $response->assertSee('Password Baru');
        $response->assertSee('Konfirmasi Password Baru');
    }

    public function test_reset_password_rejects_unmatched_confirmation(): void
    {
        $response = $this->withSession([
            'otp_email'    => 'waka@smkn1bantul.sch.id',
            'otp_verified' => true,
        ])->from(route('password.reset.form'))
          ->post(route('password.reset.post'), [
              'password'              => 'newpassword123',
              'password_confirmation' => 'differentpass123',
          ]);

        $response->assertRedirect(route('password.reset.form'));
        $response->assertSessionHasErrors(['password']);
    }

    public function test_reset_password_rejects_same_as_old_password(): void
    {
        $response = $this->withSession([
            'otp_email'    => 'waka@smkn1bantul.sch.id',
            'otp_verified' => true,
        ])->from(route('password.reset.form'))
          ->post(route('password.reset.post'), [
              'password'              => 'oldpassword123',
              'password_confirmation' => 'oldpassword123',
          ]);

        $response->assertRedirect(route('password.reset.form'));
        $response->assertSessionHasErrors(['password']);
    }

    public function test_reset_password_success_updates_password_and_redirects_to_login(): void
    {
        PasswordOtp::create([
            'email'      => 'waka@smkn1bantul.sch.id',
            'otp_hash'   => Hash::make('654321'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withSession([
            'otp_email'    => 'waka@smkn1bantul.sch.id',
            'otp_verified' => true,
        ])->post(route('password.reset.post'), [
            'password'              => 'newsecurepassword999',
            'password_confirmation' => 'newsecurepassword999',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Pastikan password user benar-benar terupdate
        $this->user->refresh();
        $this->assertTrue(Hash::check('newsecurepassword999', $this->user->password));

        // Pastikan OTP dan session dibersihkan
        $this->assertDatabaseMissing('password_otps', [
            'email' => 'waka@smkn1bantul.sch.id',
        ]);
    }
}
