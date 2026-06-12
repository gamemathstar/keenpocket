<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdashiAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithAdashi(): array
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
        $this->actingAs($admin);

        $this->post('/adashi', [
            'name' => 'Admin Adashi', 'amount_per_cycle' => 50000, 'cycle_duration_days' => 30,
            'start_date' => '2026-01-01', 'rotation_mode' => 'manual',
        ]);

        return [$admin, Adashi::first()];
    }

    public function test_pause_and_resume()
    {
        [$admin, $adashi] = $this->adminWithAdashi();

        $this->post("/adashi/{$adashi->id}/admin", ['action' => 'PAUSE'])->assertRedirect();
        $this->assertSame('PAUSED', $adashi->fresh()->status);
        $this->assertDatabaseHas('adashi_audit_logs', ['adashi_id' => $adashi->id, 'action' => 'PAUSE']);

        $this->post("/adashi/{$adashi->id}/admin", ['action' => 'RESUME'])->assertRedirect();
        $this->assertSame('ACTIVE', $adashi->fresh()->status);
    }

    public function test_adjust_contribution_records_a_paid_invoice()
    {
        [$admin, $adashi] = $this->adminWithAdashi();
        $record = AdashiRecord::where('adashi_id', $adashi->id)->first();

        $this->post("/adashi/{$adashi->id}/admin", [
            'action' => 'ADJUST_CONTRIBUTION',
            'record_id' => $record->id,
            'member_user_id' => $admin->id,
            'amount' => 50000,
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'adashi_record_id' => $record->id, 'payment_status' => 'Paid', 'amount' => 50000,
        ]);
        $this->assertSame(1, (int) $record->fresh()->paid_members_count);
    }

    public function test_record_exposes_due_date_alias()
    {
        [$admin, $adashi] = $this->adminWithAdashi();
        $record = AdashiRecord::where('adashi_id', $adashi->id)->first();

        $this->assertArrayHasKey('due_date', $record->toArray());
        $this->assertNotNull($record->due_date);
    }
}
