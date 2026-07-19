<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lead;
use App\Models\Activity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create a Manager
        $manager = User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@crm.com',
        ]);

        // 2. Create Reps
        $rep1 = User::factory()->rep()->create([
            'name' => 'Rep One',
            'email' => 'rep1@crm.com',
        ]);

        $rep2 = User::factory()->rep()->create([
            'name' => 'Rep Two',
            'email' => 'rep2@crm.com',
        ]);

        $rep3 = User::factory()->rep()->create([
            'name' => 'Rep Three',
            'email' => 'rep3@crm.com',
        ]);

        $reps = collect([$rep1, $rep2, $rep3]);

        // 3. Create some unassigned leads
        Lead::factory(5)->create([
            'assigned_to' => null,
            'status' => 'new',
        ]);

        // 4. Create leads for each rep with activities
        foreach ($reps as $rep) {
            // New leads
            Lead::factory(3)->create([
                'assigned_to' => $rep->id,
                'status' => 'new',
            ]);

            // Contacted/Qualified leads with some activities
            $leadsWithActivities = Lead::factory(5)->create([
                'assigned_to' => $rep->id,
                'status' => fn () => fake()->randomElement(['contacted', 'qualified']),
            ]);

            foreach ($leadsWithActivities as $lead) {
                Activity::factory(fake()->numberBetween(1, 4))->create([
                    'lead_id' => $lead->id,
                    'user_id' => $rep->id,
                ]);
            }

            // Won lead (MUST have at least one activity)
            $wonLead = Lead::factory()->create([
                'assigned_to' => $rep->id,
                'status' => 'won',
            ]);
            Activity::factory()->create([
                'lead_id' => $wonLead->id,
                'user_id' => $rep->id,
            ]);

            // Lost lead (MUST have at least one activity)
            $lostLead = Lead::factory()->create([
                'assigned_to' => $rep->id,
                'status' => 'lost',
            ]);
            Activity::factory()->create([
                'lead_id' => $lostLead->id,
                'user_id' => $rep->id,
            ]);
        }
    }
}
