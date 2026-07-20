<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadStatusChanged;
use App\Models\Activity;

class AutoRecordStatusChangeActivity
{
    /**
     * Handle the event.
     */
    public function handle(LeadStatusChanged $event): void
    {
        Activity::create([
            'lead_id' => $event->lead->id,
            'user_id' => auth()->id(), // User who triggered the action
            'type' => 'note',
            'body' => sprintf(
                'System: Lead status changed from "%s" to "%s".',
                $event->oldStatus,
                $event->lead->status
            ),
            'occurred_at' => now(),
        ]);
    }
}
