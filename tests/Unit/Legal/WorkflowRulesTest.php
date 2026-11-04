<?php

namespace Tests\Unit\Legal;

use App\Models\Legal\SalesCompliance\SalesComplianceApproval;
use App\Models\Legal\SalesCompliance\SalesComplianceRequest;
use App\Models\Legal\VendorOnboarding\VendorRegistration;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Aturan murni Sales Compliance & Vendor Onboarding: selisih harga, siapa yang diizinkan sebuah langkah, status yang boleh disunting. */
class WorkflowRulesTest extends TestCase
{
    #[DataProvider('variances')]
    public function test_price_variance_against_mrp(?string $final, ?string $mrp, ?float $expected): void
    {
        $request = new SalesComplianceRequest(['final_price' => $final, 'mrp_price' => $mrp]);

        $this->assertSame($expected, $request->priceVariancePct());
    }

    public static function variances(): array
    {
        return [
            'di bawah MRP'       => ['900', '1000', -10.0],
            'sama dengan MRP'    => ['1000', '1000', 0.0],
            'di atas MRP'        => ['1250', '1000', 25.0],
            'MRP nol'            => ['100', '0', null],
            'harga belum diisi'  => [null, '1000', null],
        ];
    }

    public function test_a_role_step_allows_only_holders_of_that_role(): void
    {
        $step = new SalesComplianceApproval(['approver_type' => 'role', 'approver_role_id' => 7]);

        $this->assertTrue($step->allows(10, [3, 7]));
        $this->assertFalse($step->allows(10, [3, 8]));
    }

    public function test_an_employee_step_allows_only_the_named_people(): void
    {
        $step = new SalesComplianceApproval(['approver_type' => 'employee', 'approver_employee_ids' => [4, 9]]);

        $this->assertTrue($step->allows(9, []));
        $this->assertFalse($step->allows(5, [1, 2]));
    }

    public function test_a_step_without_a_role_allows_nobody(): void
    {
        $this->assertFalse((new SalesComplianceApproval(['approver_type' => 'role', 'approver_role_id' => null]))->allows(1, [1]));
    }

    public function test_only_a_draft_sales_request_is_editable_and_only_in_review_is_open(): void
    {
        $this->assertTrue((new SalesComplianceRequest(['status' => 'draft']))->isEditable());
        $this->assertFalse((new SalesComplianceRequest(['status' => 'in_review']))->isEditable());
        $this->assertTrue((new SalesComplianceRequest(['status' => 'in_review']))->isOpen());
        $this->assertFalse((new SalesComplianceRequest(['status' => 'approved']))->isOpen());
    }

    public function test_a_vendor_registration_stays_editable_until_review_starts(): void
    {
        foreach (['draft' => true, 'submitted' => true, 'in_review' => false, 'verified' => false, 'rejected' => false] as $status => $editable) {
            $this->assertSame($editable, (new VendorRegistration(['status' => $status]))->isEditable(), $status);
        }
    }
}
