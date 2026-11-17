{{-- Tab Vendor Onboarding: Registrations | Settings --}}
@include('partials.hub-tabs', ['hubTabs' => [
    ['label' => 'Registrations', 'icon' => 'truck-field', 'route' => 'legal.vendor-onboarding.index', 'is' => array_merge(['legal/vendor-onboarding', 'legal/vendor-onboarding/create'], array_map(fn ($d) => 'legal/vendor-onboarding/' . $d . '*', range(0, 9))), 'gate' => 'legal.vendor-onboarding', 'strict' => true, 'also' => 'legal.vendor-onboarding.approve',
        'when' => fn () => app(\App\Services\Approval\ApprovalEngine::class)->isDesignatedApprover('vendor-onboarding', (int) session('user.id'))],
    ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'legal.vendor-onboarding.settings.index', 'is' => 'legal/vendor-onboarding/settings*', 'gate' => 'legal.vendor-onboarding.settings', 'strict' => true],
], 'hubHideSingle' => true])
