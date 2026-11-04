{{-- Tabs of Legal → Compliance Management: Dashboard | Reports | Content | Settings. One slug (config/compliance.php), so all four show together. --}}
@php $manageSlug = config('compliance.slugs.manage'); @endphp
@include('partials.hub-tabs', ['hubTabs' => [
    ['label' => 'Dashboard', 'icon' => 'gauge', 'route' => 'compliance.manage.dashboard', 'is' => 'legal/compliance/dashboard', 'gate' => $manageSlug, 'strict' => true],
    ['label' => 'Reports', 'icon' => 'flag', 'route' => 'compliance.manage.reports', 'is' => 'legal/compliance/reports*', 'gate' => $manageSlug, 'strict' => true,
        'badge' => fn () => \App\Models\WebReport::compliance()->where('status', 'new')->count()],
    ['label' => 'Content', 'icon' => 'book-open', 'route' => 'compliance.manage.content', 'is' => 'legal/compliance/content*', 'gate' => $manageSlug, 'strict' => true],
    ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'compliance.manage.settings', 'is' => 'legal/compliance/settings*', 'gate' => $manageSlug, 'strict' => true],
]])
