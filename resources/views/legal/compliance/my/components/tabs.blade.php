{{-- Tabs of My Compliance — open to every signed-in user, so no gate. --}}
@include('partials.accent-remap')
@include('partials.hub-tabs', ['hubTabs' => [
    ['label' => 'Home', 'icon' => 'house', 'route' => 'compliance.index', 'is' => 'compliance'],
    ['label' => 'Knowledge Base', 'icon' => 'book-open', 'route' => 'compliance.knowledge', 'is' => 'compliance/knowledge*'],
    ['label' => 'Do & Don\'t', 'icon' => 'scale-balanced', 'route' => 'compliance.rules', 'is' => 'compliance/rules*'],
    ['label' => 'Training', 'icon' => 'calendar-days', 'route' => 'compliance.training', 'is' => 'compliance/training*'],
    ['label' => 'Report a Concern', 'icon' => 'flag', 'route' => 'compliance.report', 'is' => 'compliance/report*'],
]])
