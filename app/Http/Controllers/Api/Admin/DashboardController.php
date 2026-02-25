<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use App\Models\ServiceLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard metrics for admin: counts by status for sales and service leads.
     * Optional query param: year (e.g. 2024) to filter leads by created_at year.
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->filled('year') ? (int) $request->input('year') : null;

        $salesQuery = SalesLead::query();
        $serviceQuery = ServiceLead::query();

        if ($year !== null) {
            $salesQuery->whereYear('created_at', $year);
            $serviceQuery->whereYear('created_at', $year);
        }

        $salesByStatus = $this->countByStatus($salesQuery);
        $serviceByStatus = $this->countByStatus($serviceQuery);

        return response()->json([
            'success' => true,
            'data' => [
                'salesLeads' => [
                    'total' => (int) $salesQuery->count(),
                    'yetToStart' => $salesByStatus['Yet to start'],
                    'inProgress' => $salesByStatus['In progress'],
                    'completed' => $salesByStatus['Completed'],
                ],
                'serviceLeads' => [
                    'total' => (int) $serviceQuery->count(),
                    'yetToStart' => $serviceByStatus['Yet to start'],
                    'inProgress' => $serviceByStatus['In progress'],
                    'completed' => $serviceByStatus['Completed'],
                ],
            ],
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array<string, int>
     */
    private function countByStatus($query): array
    {
        $labels = config('lead.status_labels', [
            'pending' => 'Yet to start',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ]);

        $result = [
            'Yet to start' => 0,
            'In progress' => 0,
            'Completed' => 0,
        ];

        foreach (array_keys($labels) as $status) {
            $count = (clone $query)->where('status', $status)->count();
            $result[$labels[$status]] = (int) $count;
        }

        return $result;
    }
}
