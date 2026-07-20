<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyRepOfAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The lead instance.
     *
     * @var Lead
     */
    public $lead;

    /**
     * Create a new job instance.
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info(sprintf(
            'Notification: Lead "%s" (ID: %d) has been assigned to Rep (ID: %d).',
            $this->lead->name,
            $this->lead->id,
            $this->lead->assigned_to
        ));
    }
}
