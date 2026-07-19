<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display the representative performance report.
     */
    public function repPerformance(Request $request): JsonResponse
    {
        // Enforce basic authenticated check
        if (! $request->user()->isManager() && ! $request->user()->isRep()) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $query = User::query()
            ->where('role', 'rep')
            ->select('id', 'name', 'email')
            ->withCount([
                'leads as total_leads',
                'leads as new_leads_count' => fn ($q) => $q->where('status', 'new'),
                'leads as contacted_leads_count' => fn ($q) => $q->where('status', 'contacted'),
                'leads as qualified_leads_count' => fn ($q) => $q->where('status', 'qualified'),
                'leads as won_leads_count' => fn ($q) => $q->where('status', 'won'),
                'leads as lost_leads_count' => fn ($q) => $q->where('status', 'lost'),
                'activitiesThroughLeads as total_activities'
            ])
            ->withSum('leads as total_expected_value', 'expected_value')
            ->withSum(['leads as won_expected_value' => fn ($q) => $q->where('status', 'won')], 'expected_value');

        // Scopes rep to only view their own row
        if ($request->user()->isRep()) {
            $query->where('id', $request->user()->id);
        }

        $repsData = $query->get()->map(function ($rep) {
            return [
                'rep_id' => $rep->id,
                'name' => $rep->name,
                'email' => $rep->email,
                'total_leads' => $rep->total_leads,
                'status_counts' => [
                    'new' => $rep->new_leads_count,
                    'contacted' => $rep->contacted_leads_count,
                    'qualified' => $rep->qualified_leads_count,
                    'won' => $rep->won_leads_count,
                    'lost' => $rep->lost_leads_count,
                ],
                'total_expected_value' => number_format((float) ($rep->total_expected_value ?? 0), 2, '.', ''),
                'won_expected_value' => number_format((float) ($rep->won_expected_value ?? 0), 2, '.', ''),
                'total_activities' => $rep->total_activities,
            ];
        });

        return response()->json($repsData);
    }
}
