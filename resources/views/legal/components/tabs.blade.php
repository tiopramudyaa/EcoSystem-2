{{-- Tabs of Legal → Legal Basis: Register | Settings — real routes, each with its own permission (a tab nobody may open is not drawn). Compliance Management has its own tabs: legal/compliance/partials/manage-tabs --}}
@include('partials.hub-tabs', ['hubTabs' => [
    ['label' => 'Register', 'icon' => 'book', 'route' => 'finance.legal-basis.index', 'is' => 'legal/basis', 'gate' => 'finance.legal-basis', 'strict' => true],
    ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'finance.legal-basis.settings', 'is' => 'legal/basis/settings*', 'gate' => 'finance.legal-basis.settings', 'strict' => true],
], 'hubHideSingle' => true])
