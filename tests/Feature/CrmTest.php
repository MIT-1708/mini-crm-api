<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login endpoint with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role']
            ]);
    }

    /**
     * Test login endpoint with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test that manager sees all leads, but rep sees only their assigned leads.
     */
    public function test_manager_can_see_all_leads_but_rep_can_only_see_assigned_leads()
    {
        $manager = User::factory()->manager()->create();
        $rep1 = User::factory()->rep()->create();
        $rep2 = User::factory()->rep()->create();

        Lead::factory(3)->create(['assigned_to' => $rep1->id]);
        Lead::factory(2)->create(['assigned_to' => $rep2->id]);
        Lead::factory(1)->create(['assigned_to' => null]);

        // Manager view
        $response = $this->actingAs($manager)->getJson('/api/leads');
        $response->assertStatus(200)
            ->assertJsonCount(6, 'data');

        // Rep 1 view
        $response = $this->actingAs($rep1)->getJson('/api/leads');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test won/lost transitions require at least one activity.
     */
    public function test_won_lost_status_transition_requires_activity()
    {
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $rep->id,
            'status' => 'new',
        ]);

        // Try changing status to won without activities
        $response = $this->actingAs($rep)->patchJson("/api/leads/{$lead->id}", [
            'status' => 'won',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');

        // Log an activity
        Activity::factory()->create([
            'lead_id' => $lead->id,
            'user_id' => $rep->id,
        ]);

        // Try changing status to won with activities
        $response = $this->actingAs($rep)->patchJson("/api/leads/{$lead->id}", [
            'status' => 'won',
        ]);
        $response->assertStatus(200);
        $this->assertEquals(LeadStatus::WON, $lead->fresh()->status);
    }

    /**
     * Test that only managers can assign leads.
     */
    public function test_only_manager_can_assign_leads()
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        // Rep tries to assign
        $response = $this->actingAs($rep)->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $rep->id,
        ]);
        $response->assertStatus(403);

        // Manager assigns
        $response = $this->actingAs($manager)->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $rep->id,
        ]);
        $response->assertStatus(200);
        $this->assertEquals($rep->id, $lead->fresh()->assigned_to);
    }

    /**
     * Test rep can only log activities on leads they are assigned to.
     */
    public function test_rep_can_only_log_activities_on_their_leads()
    {
        $rep1 = User::factory()->rep()->create();
        $rep2 = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep1->id]);

        // Rep 2 tries to log activity
        $response = $this->actingAs($rep2)->postJson("/api/leads/{$lead->id}/activities", [
            'type' => 'call',
            'body' => 'Spoke to lead.',
        ]);
        $response->assertStatus(403);

        // Rep 1 logs activity
        $response = $this->actingAs($rep1)->postJson("/api/leads/{$lead->id}/activities", [
            'type' => 'call',
            'body' => 'Spoke to lead.',
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test rep performance report query correctness.
     */
    public function test_rep_performance_report()
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();

        $lead1 = Lead::factory()->create([
            'assigned_to' => $rep->id,
            'status' => 'won',
            'expected_value' => 5000.00,
        ]);
        Activity::factory()->create([
            'lead_id' => $lead1->id,
            'user_id' => $rep->id,
        ]);

        $lead2 = Lead::factory()->create([
            'assigned_to' => $rep->id,
            'status' => 'new',
            'expected_value' => 3000.00,
        ]);

        $response = $this->actingAs($manager)->getJson('/api/reports/rep-performance');
        $response->assertStatus(200)
            ->assertJsonFragment([
                'rep_id' => $rep->id,
                'name' => $rep->name,
                'email' => $rep->email,
                'total_leads' => 2,
                'status_counts' => [
                    'new' => 1,
                    'contacted' => 0,
                    'qualified' => 0,
                    'won' => 1,
                    'lost' => 0,
                ],
                'total_expected_value' => '8000.00',
                'won_expected_value' => '5000.00',
                'total_activities' => 1,
            ]);
    }
}
