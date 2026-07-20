<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Lead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    /**
     * Log an activity for the specified lead.
     */
    public function store(StoreActivityRequest $request, Lead $lead): ActivityResource
    {
        $this->authorize('logActivity', $lead);

        $data = $request->validated();
        $data['occurred_at'] = $data['occurred_at'] ?? now();
        $data['user_id'] = $request->user()->id;

        $activity = $lead->activities()->create($data);

        return new ActivityResource($activity->load('user'));
    }
}
