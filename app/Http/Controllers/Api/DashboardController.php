<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lead;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = Auth::user();
        $role = $request->get('role', $user->getRoleNames()->first());
        
        // Cache key based on user, role, and organization
        $cacheKey = "dashboard_stats_{$user->id}_{$role}_{$user->organization_id}_" . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 3600, function() use ($user, $role) { // Cache for 1 hour
            // Single optimized query with all aggregations
            $baseQuery = $this->getFilteredLeadsQuery($user);
            
            // Get all stats in a single query using DB aggregation
            $stats = $baseQuery->selectRaw('
                COUNT(*) as total_leads,
                COUNT(CASE WHEN status IN ("assigned_to_manager", "assigned_to_agent", "contacted", "qualified") THEN 1 END) as active_leads,
                COUNT(CASE WHEN status = "converted" THEN 1 END) as converted_leads,
                COALESCE(SUM(CASE WHEN status = "converted" THEN revenue END), 0) as total_revenue,
                COUNT(CASE WHEN status = "new" THEN 1 END) as new_leads,
                COUNT(CASE WHEN status = "assigned_to_manager" THEN 1 END) as assigned_to_manager,
                COUNT(CASE WHEN status = "assigned_to_agent" THEN 1 END) as assigned_to_agent,
                COUNT(CASE WHEN status = "declined_by_manager" THEN 1 END) as declined_by_manager,
                COUNT(CASE WHEN status = "declined_by_agent" THEN 1 END) as declined_by_agent,
                COUNT(CASE WHEN source = "Website" THEN 1 END) as website_leads,
                COUNT(CASE WHEN source = "Referral" THEN 1 END) as referral_leads,
                COUNT(CASE WHEN source = "Cold Call" THEN 1 END) as cold_call_leads
            ')->first();
            
            // This month stats in separate optimized query
            $thisMonth = now()->startOfMonth();
            $monthStats = $baseQuery->where('created_at', '>=', $thisMonth)
                ->selectRaw('
                    COUNT(*) as leads_this_month,
                    COALESCE(SUM(CASE WHEN status = "converted" AND updated_at >= ? THEN revenue END), 0) as revenue_this_month
                ', [$thisMonth])
                ->first();
            
            // Calculate derived metrics
            $conversionRate = $stats->total_leads > 0 ? ($stats->converted_leads / $stats->total_leads) : 0;
            $averageDealValue = $stats->converted_leads > 0 ? ($stats->total_revenue / $stats->converted_leads) : 0;
            
            // Get top performers efficiently (only if user has access)
            $topPerformers = [];
            if ($user->hasRole(['Admin', 'Group Director', 'Partner Director'])) {
                $topPerformers = $this->getTopPerformers($user);
            }
            
            // Recent activity (limited and efficient)
            $recentActivity = $this->getRecentActivity($user);
            
            return [
                'success' => true,
                'data' => [
                    'totalLeads' => (int) $stats->total_leads,
                    'activeLeads' => (int) $stats->active_leads,
                    'convertedLeads' => (int) $stats->converted_leads,
                    'totalRevenue' => (float) $stats->total_revenue,
                    'conversionRate' => round($conversionRate, 3), // Frontend expects decimal
                    'averageDealValue' => round($averageDealValue, 0),
                    'leadsThisMonth' => (int) $monthStats->leads_this_month,
                    'revenueThisMonth' => (float) $monthStats->revenue_this_month,
                    'topPerformers' => $topPerformers,
                    'recentActivity' => $recentActivity,
                    'leadsByStatus' => [
                        'new' => (int) $stats->new_leads,
                        'assigned_to_manager' => (int) $stats->assigned_to_manager,
                        'assigned_to_agent' => (int) $stats->assigned_to_agent,
                        'converted' => (int) $stats->converted_leads,
                        'declined_by_manager' => (int) $stats->declined_by_manager,
                        'declined_by_agent' => (int) $stats->declined_by_agent,
                    ],
                    'leadsBySource' => [
                        'Website' => (int) $stats->website_leads,
                        'Referral' => (int) $stats->referral_leads,
                        'Cold Call' => (int) $stats->cold_call_leads,
                    ],
                    'monthlyTrends' => [
                        [
                            'month' => now()->format('Y-m'),
                            'leads' => (int) $monthStats->leads_this_month,
                            'revenue' => (float) $monthStats->revenue_this_month,
                        ]
                    ],
                ],
            ];
        });
    }
    
    /**
     * Get filtered leads query based on user role and permissions
     */
    private function getFilteredLeadsQuery($user)
    {
        $query = Lead::query();
        
        if ($user->hasRole(['Admin', 'Group Director'])) {
            // Admin and Group Director see all data - no additional filtering
        } elseif ($user->hasRole('Partner Director')) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->hasRole('Sales Manager')) {
            $query->where(function($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhere('assigned_by_id', $user->id);
            });
        } else {
            $query->where('assigned_to_id', $user->id);
        }
        
        return $query;
    }
    
    /**
     * Get top performers efficiently
     */
    private function getTopPerformers($user)
    {
        $query = User::select('users.id', 'users.first_name', 'users.last_name')
            ->join('leads', 'users.id', '=', 'leads.assigned_to_id')
            ->where('leads.status', 'converted')
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->selectRaw('
                COUNT(leads.id) as leads_count,
                COALESCE(SUM(leads.revenue), 0) as total_revenue
            ')
            ->orderByDesc('total_revenue')
            ->limit(3);
            
        // Apply same role-based filtering
        if ($user->hasRole('Partner Director')) {
            $query->where('leads.organization_id', $user->organization_id);
        } elseif ($user->hasRole('Sales Manager')) {
            $query->where(function($q) use ($user) {
                $q->where('leads.organization_id', $user->organization_id)
                  ->orWhere('leads.assigned_by_id', $user->id);
            });
        }
        
        return $query->get()->map(function($performer) {
            return [
                'id' => $performer->id,
                'name' => $performer->first_name . ' ' . $performer->last_name,
                'leads' => (int) $performer->leads_count,
                'revenue' => (float) $performer->total_revenue,
            ];
        })->toArray();
    }
    
    /**
     * Get recent activity efficiently
     */
    private function getRecentActivity($user)
    {
        $query = Lead::select('leads.id', 'leads.updated_at', 'leads.assigned_by_id', 'leads.assigned_to_id')
            ->with(['assignedTo:id,first_name', 'assignedBy:id,first_name,last_name'])
            ->orderBy('updated_at', 'desc')
            ->limit(5);
            
        // Apply same role-based filtering as main query
        if ($user->hasRole('Partner Director')) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->hasRole('Sales Manager')) {
            $query->where(function($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhere('assigned_by_id', $user->id);
            });
        } elseif (!$user->hasRole(['Admin', 'Group Director'])) {
            $query->where('assigned_to_id', $user->id);
        }
        
        return $query->get()->map(function($lead) {
            return [
                'id' => 'activity_' . $lead->id,
                'type' => 'lead_assigned',
                'description' => 'Lead assigned to ' . ($lead->assignedTo->first_name ?? 'Unknown'),
                'timestamp' => $lead->updated_at->toISOString(),
                'userId' => $lead->assigned_by_id,
                'userName' => $lead->assignedBy ? 
                    $lead->assignedBy->first_name . ' ' . $lead->assignedBy->last_name : 'System',
            ];
        })->toArray();
    }
    
    public function teamPerformance(Request $request, string $managerId)
    {
        $manager = User::findOrFail($managerId);
        
        // Verify access
        if (!Auth::user()->hasRole(['Admin', 'Group Director']) && Auth::id() !== $managerId) {
            abort(403, 'Unauthorized');
        }
        
        // Cache team performance data for 30 minutes
        $cacheKey = "team_performance_{$managerId}_" . now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 1800, function() use ($manager) { // Cache for 30 minutes
            // Single optimized query to get all team data with aggregations
            $teamData = User::select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.is_active', 'users.updated_at')
                ->where(function($q) use ($manager) {
                    $q->where('organization_id', $manager->organization_id)
                      ->where('created_by', $manager->id);
                })
                ->whereHas('roles', function($q) {
                    $q->where('name', 'Sales Agent');
                })
                ->leftJoin('leads as assigned_leads', function($join) {
                    $join->on('users.id', '=', 'assigned_leads.assigned_to_id');
                })
                ->leftJoin('appointments', function($join) {
                    $join->on('users.id', '=', 'appointments.scheduled_by');
                })
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.is_active', 'users.updated_at')
                ->selectRaw('
                    COUNT(assigned_leads.id) as total_leads,
                    COUNT(CASE WHEN assigned_leads.status IN ("assigned_to_agent", "contacted", "qualified") THEN 1 END) as active_leads,
                    COUNT(CASE WHEN assigned_leads.status = "converted" THEN 1 END) as converted_leads,
                    COALESCE(SUM(CASE WHEN assigned_leads.status = "converted" THEN assigned_leads.revenue END), 0) as revenue,
                    COUNT(appointments.id) as appointments_scheduled,
                    COUNT(CASE WHEN appointments.status = "completed" THEN 1 END) as appointments_completed
                ')
                ->get()
                ->map(function($agent) {
                    $conversionRate = $agent->total_leads > 0 ? ($agent->converted_leads / $agent->total_leads) * 100 : 0;
                    $appointmentSuccessRate = $agent->appointments_scheduled > 0 ? 
                        ($agent->appointments_completed / $agent->appointments_scheduled) * 100 : 0;
                    $performanceScore = ($conversionRate + $appointmentSuccessRate) / 2;
                    
                    return [
                        'id' => $agent->id,
                        'firstName' => $agent->first_name,
                        'lastName' => $agent->last_name,
                        'email' => $agent->email,
                        'totalLeads' => (int) $agent->total_leads,
                        'activeLeads' => (int) $agent->active_leads,
                        'convertedLeads' => (int) $agent->converted_leads,
                        'revenue' => (float) $agent->revenue,
                        'conversionRate' => round($conversionRate, 1),
                        'appointmentsScheduled' => (int) $agent->appointments_scheduled,
                        'appointmentsCompleted' => (int) $agent->appointments_completed,
                        'appointmentSuccessRate' => round($appointmentSuccessRate, 1),
                        'performanceScore' => round($performanceScore, 1),
                        'isActive' => (bool) $agent->is_active,
                        'lastActivity' => $agent->updated_at->toISOString(),
                    ];
                });
            
            // Calculate team aggregates
            $teamSize = $teamData->count();
            $totalLeads = $teamData->sum('totalLeads');
            $totalRevenue = $teamData->sum('revenue');
            $averageConversionRate = $teamData->avg('conversionRate') ?? 0;
            $appointmentSuccessRate = $teamData->avg('appointmentSuccessRate') ?? 0;
            $teamEfficiency = $teamData->avg('performanceScore') ?? 0;
            
            $topPerformers = $teamData->sortByDesc('performanceScore')->take(3)->values();
            $improvementNeeded = $teamData->where('performanceScore', '<', 50)->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'teamSize' => $teamSize,
                    'totalLeads' => $totalLeads,
                    'totalRevenue' => $totalRevenue,
                    'averageConversionRate' => round($averageConversionRate, 1),
                    'appointmentSuccessRate' => round($appointmentSuccessRate, 1),
                    'teamEfficiency' => round($teamEfficiency, 1),
                    'agents' => $teamData->values(),
                    'topPerformers' => $topPerformers,
                    'improvementNeeded' => $improvementNeeded,
                ],
                'message' => 'Team performance data retrieved successfully',
            ]);
        });
    }
    
    /**
     * Clear dashboard cache (for admins when data updates are needed immediately)
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();
        
        // Only admins can clear cache
        if (!$user->hasRole(['Admin', 'Group Director'])) {
            abort(403, 'Unauthorized');
        }
        
        // Clear all dashboard-related cache
        $pattern = 'dashboard_stats_*';
        $teamPattern = 'team_performance_*';
        
        // Laravel doesn't support wildcard cache clearing by default
        // So we'll clear the current user's cache and log the action
        $userCacheKey = "dashboard_stats_{$user->id}_*";
        
        // Clear cache tags if using Redis or other advanced cache drivers
        try {
            Cache::flush(); // This clears all cache - use carefully
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }
}
