<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * The lead instance.
     *
     * @var Lead
     */
    public $lead;

    /**
     * The old status of the lead.
     *
     * @var string
     */
    public $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Lead $lead, string $oldStatus)
    {
        $this->lead = $lead;
        $this->oldStatus = $oldStatus;
    }
}
