<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\ComplianceAlert;
use App\Models\Compliance\ComplianceArticle;
use App\Models\Compliance\ComplianceCategory;
use App\Models\Compliance\ComplianceRule;
use App\Models\Compliance\ComplianceSession;
use Illuminate\Http\Request;

/**
 * Legal → My Compliance (URL /compliance, every signed-in user, no Menu Access slug): the read-only pages staff use — index (home), knowledge base,
 * one article, Do & Don't (rules), training and search. One view per page in resources/views/legal/compliance/my/. Only rows switched on ("Visible in the portal") are shown. Changes nothing
 * except the article view counter. What the pages hold is managed in Legal → Compliance Management (ContentController, SettingsController). Reporting a concern is
 * MyReportController.
 */
class MyComplianceController extends Controller
{
    public function index()
    {
        return view('legal.compliance.my.index', [
            'stats' => [
                'articles' => ComplianceArticle::where('is_active', true)->count(),
                'rules' => ComplianceRule::where('is_active', true)->count(),
                'done' => ComplianceSession::where('status', 'completed')->count(),
                'sessions' => ComplianceSession::count(),
            ],
            'alerts' => ComplianceAlert::where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => $this->categories(),
            'featured' => ComplianceArticle::with('category')->where('is_active', true)->where('is_featured', true)
                ->latest('updated_at')->limit(config('compliance.portal.featured_limit'))->get(),
            'upcoming' => ComplianceSession::where('status', 'upcoming')->orderBy('session_date')->orderBy('start_time')
                ->limit(config('compliance.portal.upcoming_limit'))->get(),
        ]);
    }

    public function knowledge(Request $request)
    {
        $categories = $this->categories();
        $active = $categories->firstWhere('slug', $request->query('cat'));

        return view('legal.compliance.my.knowledge', [
            'categories' => $categories,
            'active' => $active,
            'articles' => ComplianceArticle::with('category')->where('is_active', true)
                ->when($active, fn ($q) => $q->where('category_id', $active->id))
                ->orderByDesc('is_featured')->orderByDesc('updated_at')->get(),
        ]);
    }

    public function article(int $id)
    {
        $article = ComplianceArticle::with('category')->where('is_active', true)->findOrFail($id);

        // Count a reader once per session, not once per reload.
        if (!in_array($id, (array) session('compliance_seen', []), true)) {
            $article->increment('views');
            session()->push('compliance_seen', $id);
        }

        return view('legal.compliance.my.article', [
            'article' => $article,
            'related' => ComplianceArticle::where('is_active', true)->where('category_id', $article->category_id)->where('id', '!=', $id)
                ->limit(config('compliance.portal.related_limit'))->get(),
        ]);
    }

    public function rules()
    {
        $rules = ComplianceRule::with('category')->where('is_active', true)->orderBy('sort_order')->get();

        return view('legal.compliance.my.rules', ['dos' => $rules->where('type', 'do'), 'donts' => $rules->where('type', 'dont')]);
    }

    public function training()
    {
        $sessions = ComplianceSession::orderByDesc('session_date')->orderByDesc('start_time')->get();

        return view('legal.compliance.my.training', [
            'periods' => $sessions->groupBy(fn ($s) => $s->session_date->format('F Y')),
            'total' => $sessions->count(),
            'done' => $sessions->where('status', 'completed')->count(),
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $articles = collect();
        $rules = collect();
        $alerts = collect();
        $sessions = collect();

        if (mb_strlen($q) >= config('compliance.portal.search_min_chars')) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $articles = ComplianceArticle::with('category')->where('is_active', true)
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('tags', 'like', $like)->orWhere('content', 'like', $like))
                ->orderByRaw('title LIKE ? DESC', [$like])->limit(config('compliance.portal.search_article_limit'))->get();
            $rules = ComplianceRule::where('is_active', true)
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like))->limit(config('compliance.portal.search_rule_limit'))->get();
            $alerts = ComplianceAlert::where('is_active', true)
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like))->limit(config('compliance.portal.search_rule_limit'))->get();
            $sessions = ComplianceSession::where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('facilitator', 'like', $like))
                ->orderByDesc('session_date')->limit(config('compliance.portal.search_rule_limit'))->get();
        }

        return view('legal.compliance.my.search', ['q' => $q, 'articles' => $articles, 'rules' => $rules, 'alerts' => $alerts, 'sessions' => $sessions]);
    }

    private function categories()
    {
        return ComplianceCategory::where('is_active', true)->withCount(['articles' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get();
    }
}
