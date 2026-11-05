<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\EmployeeContract;
use App\Services\Contracts\ExistingContractImporter;
use Illuminate\Http\Request;

/**
 * HR & General → Contract → "Add existing contract": mencatat kontrak fisik yang sudah berlaku beserta scan PDF-nya.
 *
 * Izin: slug fungsi `general.contracts.import` (dijaga `menu:`) DITAMBAH kotak Create / Edit / Delete menu Contracts
 * (dijaga `menu.can` di rute). Aturan bisnis ada di ExistingContractImporter, bukan di sini.
 */
class ExistingContractController extends Controller
{
    public function __construct(private ExistingContractImporter $importer) {}

    public function store(Request $request, int $employeeId)
    {
        $data = $request->validate($this->rules(true), $this->messages());

        try {
            $contract = $this->importer->import($employeeId, $data, $request->file('scan'), (int) session('user.id'));
        } catch (\DomainException $e) {
            return $this->failed($e, 'add', null);
        }

        return redirect()->route('general.contracts.show', $employeeId)
            ->with('success', "Contract {$contract->contract_number} recorded with its scanned copy.");
    }

    public function update(Request $request, int $contractId)
    {
        $contract = EmployeeContract::findOrFail($contractId);
        $data = $request->validate($this->rules(false), $this->messages());

        try {
            $this->importer->update($contract, $data, $request->file('scan'), (int) session('user.id'));
        } catch (\DomainException $e) {
            return $this->failed($e, 'edit', $contractId);
        }

        return redirect()->route('general.contracts.show', $contract->employee_id)->with('success', "Contract {$data['contract_number']} saved.");
    }

    public function destroy(int $contractId)
    {
        $contract = EmployeeContract::findOrFail($contractId);

        try {
            $this->importer->remove($contract);
        } catch (\DomainException $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }

        return redirect()->route('general.contracts.show', $contract->employee_id)->with('success', "Contract {$contract->contract_number} and its scanned copy were removed.");
    }

    private function rules(bool $scanRequired): array
    {
        return [
            'contract_number' => 'required|string|max:100',
            'contract_type'   => 'required|string|max:100',
            'status'          => 'required|string|max:20',
            'start_date'      => 'required|date_format:Y-m-d',
            'end_date'        => 'nullable|date_format:Y-m-d',
            'signed_date'     => 'nullable|date_format:Y-m-d',
            'position'        => 'nullable|string|max:255',
            'position_type'   => 'nullable|string|max:100',
            'department'      => 'nullable|string|max:255',
            'work_location'   => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:2000',
            'scan'            => [$scanRequired ? 'required' : 'nullable', 'file', 'max:' . ExistingContractImporter::maxKb()],
        ];
    }

    private function messages(): array
    {
        return [
            'scan.required' => 'Attach the scanned contract as a PDF.',
            'scan.max'      => 'The PDF is larger than ' . round(ExistingContractImporter::maxKb() / 1024) . ' MB.',
            'scan.uploaded' => 'The file could not be uploaded. It may be larger than the server allows; try a smaller scan.',
        ];
    }

    /** Gagal aturan bisnis: kembali dengan isian utuh (kecuali berkas, yang harus dipilih lagi) dan dialog terbuka lagi. */
    private function failed(\DomainException $e, string $mode, ?int $contractId)
    {
        return back()->withInput()->withErrors(['contract' => $e->getMessage()])
            ->with('reopen_existing', ['mode' => $mode, 'id' => $contractId]);
    }
}
