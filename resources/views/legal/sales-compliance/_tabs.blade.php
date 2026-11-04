{{-- Tab Sales Compliance Approval: Requests | Settings — dua rute sungguhan, masing-masing dengan izinnya sendiri (tab yang tak boleh dibuka tidak digambar). --}}
@include('partials.hub-tabs', ['hubTabs' => [
    ['label' => 'Requests', 'icon' => 'file-signature', 'route' => 'legal.sales-compliance.index', 'is' => array_merge(['legal/sales-compliance', 'legal/sales-compliance/create'], array_map(fn ($d) => 'legal/sales-compliance/' . $d . '*', range(0, 9))), 'gate' => 'legal.sales-compliance', 'strict' => true, 'also' => 'legal.sales-compliance.approve',
        'when' => fn () => app(\App\Services\Approval\ApprovalEngine::class)->isDesignatedApprover('sales-compliance', (int) session('user.id'))],
    ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'legal.sales-compliance.settings.index', 'is' => 'legal/sales-compliance/settings*', 'gate' => 'legal.sales-compliance.settings', 'strict' => true],
], 'hubHideSingle' => true])
