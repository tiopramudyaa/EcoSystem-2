<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\ComplianceArticle;
use App\Models\Compliance\ComplianceSession;
use App\Models\WebReport;
use App\Services\Compliance\ComplianceCatalog;

/**
 * Legal → Compliance Management → Dashboard tab (URL /legal/compliance/dashboard): what My Compliance holds and what needs attention
 * (new and overdue reports, hidden content, upcoming training). Changes nothing.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $sections = ComplianceCatalog::sections();
        $channel = WebReport::CHANNEL_COMPLIANCE;

        return view('legal.compliance.management.dashboard', [
            'sections' => $sections,
            'counts' => collect($sections)->map(fn ($s) => ComplianceCatalog::query($s)->count()),
            'newReports' => WebReport::compliance()->where('status', 'new')->count(),
            'overdueReports' => WebReport::compliance()->whereNotIn('status', WebReport::finalStatuses($channel))->where('due_at', '<', now())->count(),
            'hidden' => collect($sections)->only(['articles', 'categories', 'rules'])->map(fn ($s) => $s['model']::where('is_active', false)->count()),
            'upcoming' => ComplianceSession::where('status', 'upcoming')->orderBy('session_date')->limit(5)->get(),
            'recent' => ComplianceArticle::with('category')->orderByDesc('updated_at')->limit(5)->get(),
            'topViewed' => ComplianceArticle::where('is_active', true)->orderByDesc('views')->limit(5)->get(),
        ]);
    }
}
