<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $customersCount = Customer::where('company_id', $companyId)->count();

        $leadsCount = Lead::where('company_id', $companyId)->count();

        $openLeadsCount = Lead::where('company_id', $companyId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->count();

        $wonLeadsCount = Lead::where('company_id', $companyId)
            ->where('stage', 'won')
            ->count();

        $pipelineValue = Lead::where('company_id', $companyId)
            ->whereNotIn('stage', ['lost'])
            ->sum('value');

        $pendingTasksCount = Task::where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        $upcomingAppointmentsCount = Appointment::where('company_id', $companyId)
            ->where('starts_at', '>=', now())
            ->count();

        $recentCustomers = Customer::where('company_id', $companyId)
            ->latest()
            ->take(5)
            ->get();

        $recentLeads = Lead::where('company_id', $companyId)
            ->with('customer')
            ->latest()
            ->take(5)
            ->get();

        $upcomingAppointments = Appointment::where('company_id', $companyId)
            ->where('starts_at', '>=', now())
            ->with('customer')
            ->orderBy('starts_at')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'customers' => $customersCount,
                'leads' => $leadsCount,
                'open_leads' => $openLeadsCount,
                'won_leads' => $wonLeadsCount,
                'pipeline_value' => $pipelineValue,
                'pending_tasks' => $pendingTasksCount,
                'upcoming_appointments' => $upcomingAppointmentsCount,
            ],

            'recent_customers' => $recentCustomers,
            'recent_leads' => $recentLeads,
            'upcoming_appointments' => $upcomingAppointments,
        ]);
    }
}