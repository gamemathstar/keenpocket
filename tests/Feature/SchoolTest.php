<?php

namespace Tests\Feature;

use App\Models\FeeItem;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        $u = User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
        foreach ($attrs as $k => $v) {
            $u->$k = $v;
        }
        $u->save();

        return $u;
    }

    public function test_create_school_is_gated_by_enrollment()
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/school/create')->assertStatus(403);

        $user->can_create_school = true;
        $user->save();
        $this->actingAs($user)->get('/school/create')->assertStatus(200);
    }

    public function test_super_admin_grants_access()
    {
        $admin = $this->makeUser(['is_super_admin' => true]);
        $user = $this->makeUser();

        $this->actingAs($admin)->post("/super-admin/{$user->id}/grant")->assertRedirect();
        $this->assertTrue((bool) $user->fresh()->can_create_school);

        // A non-super-admin cannot grant.
        $this->actingAs($this->makeUser())->post("/super-admin/{$user->id}/grant")->assertStatus(403);
    }

    public function test_fee_setup_payment_and_parent_dashboard()
    {
        $owner = $this->makeUser(['can_create_school' => true]);
        $this->actingAs($owner)->post('/school', ['name' => 'K School'])->assertRedirect();
        $school = School::where('owner_id', $owner->id)->first();
        $this->assertNotNull($school);

        // A class with two term-1 fee items totalling 20,000.
        $this->actingAs($owner)->post("/school/{$school->id}/classes", ['name' => 'JSS1'])->assertRedirect();
        $class = SchoolClass::where('school_id', $school->id)->first();
        $this->actingAs($owner)->post("/school/{$school->id}/fee-items", ['school_class_id' => $class->id, 'term' => 1, 'name' => 'Tuition', 'amount' => 15000]);
        $this->actingAs($owner)->post("/school/{$school->id}/fee-items", ['school_class_id' => $class->id, 'term' => 1, 'name' => 'Books', 'amount' => 5000]);
        $this->assertSame(20000, $class->fresh()->termFee(1));

        // Add a student under a brand-new parent (created by phone).
        $this->actingAs($owner)->post("/school/{$school->id}/students", [
            'name' => 'Aisha', 'school_class_id' => $class->id,
            'parent_phone' => '08055554444', 'parent_name' => 'Mr Bello',
        ])->assertRedirect();
        $student = Student::where('school_id', $school->id)->first();
        $parent = User::where('phone_number', '08055554444')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($parent);

        // Record a 12,000 term-1 payment.
        $this->actingAs($owner)->post("/school/{$school->id}/payments", [
            'student_id' => $student->id, 'term' => 1, 'amount' => 12000,
        ])->assertRedirect();
        $this->assertSame(12000, $student->fresh()->paidForTerm(1));

        // Parent dashboard shows the child, fee and pending balance (20k - 12k = 8k).
        $this->actingAs($parent)->get('/my-children')
            ->assertStatus(200)->assertSee('Aisha')->assertSee('8,000');
    }
}
