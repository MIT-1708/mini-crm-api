<?php

namespace App\Http\Controllers\Api;

use App\Events\LeadStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Jobs\NotifyRepOfAssignment;
use App\Models\Lead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the leads.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::query()->with(['assignedRep']);

        // Scope to Rep if user is a Rep
        if ($request->user()->isRep()) {
            $query->where('assigned_to', $request->user()->id);
        } else {
            // Managers can filter by rep
            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->input('assigned_to'));
            }
        }

        // Filtering by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filtering by source
        if ($request->has('source')) {
            $query->where('source', $request->input('source'));
        }

        // Searching by name, email, company (case-insensitive)
        if ($request->has('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                    ->orWhere('email', 'ilike', $search)
                    ->orWhere('company', 'ilike', $search);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'expected_value'])) {
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $leads = $query->paginate($request->input('per_page', 15));

        return LeadResource::collection($leads);
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        $data = $request->validated();

        // Enforce won/lost activity requirement if status is set initially
        if (isset($data['status']) && in_array($data['status'], ['won', 'lost'])) {
            throw ValidationException::withMessages([
                'status' => ['A lead must have at least one activity logged before its status can be changed to won or lost.'],
            ]);
        }

        $lead = Lead::create($data);

        // Dispatch Assignment job if assigned to someone
        if ($lead->assigned_to) {
            NotifyRepOfAssignment::dispatch($lead);
        }

        return (new LeadResource($lead))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): LeadResource
    {
        $this->authorize('view', $lead);

        $lead->load(['assignedRep', 'activities.user']);

        return new LeadResource($lead);
    }

    /**
     * Update the specified lead in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): LeadResource
    {
        $this->authorize('update', $lead);

        $data = $request->validated();

        // Enforce won/lost activity rule
        if (isset($data['status']) && in_array($data['status'], ['won', 'lost'])) {
            if ($lead->status !== $data['status']) {
                if (! $lead->activities()->exists()) {
                    throw ValidationException::withMessages([
                        'status' => ['A lead must have at least one activity logged before its status can be changed to won or lost.'],
                    ]);
                }
            }
        }

        $oldAssignedTo = $lead->assigned_to;
        $lead->update($data);

        // Dispatch Assignment job if assignee has changed
        if ($lead->assigned_to && $lead->assigned_to !== $oldAssignedTo) {
            NotifyRepOfAssignment::dispatch($lead);
        }

        // Fire status changed event if status changed
        if (isset($data['status']) && $data['status'] !== $lead->getOriginal('status')) {
            event(new LeadStatusChanged($lead, $lead->getOriginal('status')));
        }

        return new LeadResource($lead);
    }

    /**
     * Assign or reassign the lead.
     */
    public function assign(AssignLeadRequest $request, Lead $lead): LeadResource
    {
        $this->authorize('assign', $lead);

        $data = $request->validated();
        $oldAssignedTo = $lead->assigned_to;

        $lead->update([
            'assigned_to' => $data['assigned_to'],
        ]);

        if ($lead->assigned_to && $lead->assigned_to !== $oldAssignedTo) {
            NotifyRepOfAssignment::dispatch($lead);
        }

        return new LeadResource($lead->load('assignedRep'));
    }
}
