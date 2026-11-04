<?php

namespace App\Services\Legal;

use App\Models\Employee;
use App\Models\Letterhead;
use App\Models\Letters\LetterSignatory;
use App\Services\Contracts\ContractService;
use App\Services\External\ExternalContractService;
use App\Support\Contracts\SigningRules;
use Illuminate\Support\Facades\DB;

/**
 * The summary cards of the Legal page — counts only, each card from ONE source and shown only when the viewer's slug for that source
 * allows it (a card never reveals data of a page the person could not open). Every card links to the existing page.
 *
 *   Contracts          general.contracts.list            by status, ending in 30 days, awaiting signature
 *   External contracts general.external.contracts        by status
 *   Document Identity  general.letters.settings.*        letterheads and signers (shared with HR letters)
 *   Sales Compliance   legal.sales-compliance[.approve]  requests by status (own only, unless the viewer decides on them)
 *   Vendor Onboarding  legal.vendor-onboarding[.approve] registrations by status
 */
class LegalDashboardCards
{
    public function __construct(private ContractService $contracts, private ExternalContractService $external) {}

    /** @return array<string, array{title: string, icon: string, link: string, stats: array}> */
    public function for(?Employee $employee): array
    {
        $can = fn (string $slug): bool => (bool) $employee?->canAccessMenu($slug);
        $cards = [];

        if ($can('general.contracts.list')) {
            $s = $this->contracts->summary();
            $cards['contracts'] = [
                'title' => 'Contracts', 'icon' => 'file-contract', 'link' => route('general.contracts.index'),
                'stats' => [
                    ['Active', $s['active'], 'green'],
                    ['Draft', $s['draft'], 'amber'],
                    ['Ending in ' . (int) \App\Services\Management\EmployeeMasterData::rule('contract', 'ending_soon_days') . ' days', $s['ending'], 'red'],
                    ['Employees without a contract', $s['none'], 'gray'],
                ],
            ];

            $stages = DB::table('employee_contract')->whereIn('sign_stage', [SigningRules::WITH_EMPLOYEE, SigningRules::WITH_LEGAL, SigningRules::WITH_APPROVER])
                ->selectRaw('sign_stage, COUNT(*) as n')->groupBy('sign_stage')->pluck('n', 'sign_stage');
            $cards['signing'] = [
                'title' => 'Awaiting signature', 'icon' => 'signature', 'link' => route('general.contracts.index'),
                'stats' => [
                    ['With the employee', (int) ($stages[SigningRules::WITH_EMPLOYEE] ?? 0), 'amber'],
                    ['With Legal', (int) ($stages[SigningRules::WITH_LEGAL] ?? 0), 'red'],
                    ['With the approver', (int) ($stages[SigningRules::WITH_APPROVER] ?? 0), 'amber'],
                ],
            ];
        }

        if ($can('general.external.contracts')) {
            $s = $this->external->summary();
            $cards['external'] = [
                'title' => 'External contracts', 'icon' => 'user-tie', 'link' => route('general.external.contracts.index'),
                'stats' => [
                    ['All contracts', $s['total'], 'gray'],
                    ['Active', $s['active'], 'green'],
                    ['Draft', $s['draft'], 'amber'],
                ],
            ];
        }

        if ($can('legal.sales-compliance') || $can('legal.sales-compliance.approve')) {
            // Pemegang `.approve` melihat semua permintaan; yang lain hanya miliknya — sama dengan daftarnya.
            $me  = (int) $employee?->employee_id;
            $all = $can('legal.sales-compliance.approve');
            $n   = DB::table('sales_compliance_requests')
                ->when(!$all, fn ($q) => $q->where(fn ($w) => $w->where('employee_id', $me)->orWhere('created_by', $me)))
                ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
            $cards['sales-compliance'] = [
                'title' => 'Sales Compliance', 'icon' => 'file-shield', 'link' => route('legal.sales-compliance.index'),
                'stats' => [
                    ['In review', (int) ($n['in_review'] ?? 0), 'amber', route('legal.sales-compliance.index', ['status' => 'in_review'])],
                    ['Approved', (int) ($n['approved'] ?? 0), 'green', route('legal.sales-compliance.index', ['status' => 'approved'])],
                    ['Rejected', (int) ($n['rejected'] ?? 0), 'red', route('legal.sales-compliance.index', ['status' => 'rejected'])],
                    ['Draft', (int) ($n['draft'] ?? 0), 'gray', route('legal.sales-compliance.index', ['status' => 'draft'])],
                ],
            ];
        }

        if ($can('legal.vendor-onboarding') || $can('legal.vendor-onboarding.approve')) {
            $n = DB::table('vendor_registrations')->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
            $unlinked = DB::table('vendor_registrations')->where('status', 'verified')->whereNull('customer_id')->count();
            $cards['vendor-onboarding'] = [
                'title' => 'Vendor Onboarding', 'icon' => 'truck-field', 'link' => route('legal.vendor-onboarding.index'),
                'stats' => [
                    ['Waiting to start review', (int) ($n['submitted'] ?? 0), 'amber', route('legal.vendor-onboarding.index', ['status' => 'submitted'])],
                    ['In review', (int) ($n['in_review'] ?? 0), 'amber', route('legal.vendor-onboarding.index', ['status' => 'in_review'])],
                    ['Verified', (int) ($n['verified'] ?? 0), 'green', route('legal.vendor-onboarding.index', ['status' => 'verified'])],
                    ['Verified, no Business Partner yet', $unlinked, 'red', route('legal.vendor-onboarding.index', ['status' => 'verified'])],
                ],
            ];
        }

        $identity = [];
        if ($can('general.letters.settings.letterheads')) {
            $identity[] = ['Letterheads', Letterhead::count(), route('general.document-identity.index', ['section' => 'letterheads'])];
        }
        if ($can('general.letters.settings.signers')) {
            $identity[] = ['Active signers', LetterSignatory::where('is_active', true)->count(), route('general.document-identity.index', ['section' => 'signers'])];
        }
        if ($identity) {
            $cards['identity'] = [
                'title' => 'Document Identity', 'icon' => 'id-card', 'link' => $identity[0][2],
                'stats' => array_map(fn ($i) => [$i[0], $i[1], 'gray', $i[2]], $identity),
            ];
        }

        return $cards;
    }
}
