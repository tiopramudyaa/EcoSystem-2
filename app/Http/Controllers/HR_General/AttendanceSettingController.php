<?php

namespace App\Http\Controllers\HR_General;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceSetting;
use App\Models\Attendance\AttendanceSource;
use App\Models\Attendance\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Pengaturan global modul Attendance.
 *
 * Halaman ini adalah KATUP PENGAMAN modul: bila kebijakan yang dipilih ternyata
 * memblokir karyawan melakukan presensi - misalnya lokasi diwajibkan padahal
 * sebagian perangkat tidak dapat memberikannya - pemilik sistem harus bisa
 * melonggarkannya sendiri tanpa menunggu perubahan kode.
 */
class AttendanceSettingController extends Controller
{
    public function edit()
    {
        return view('hr-general.attendance.settings', [
            'settings' => AttendanceSetting::current(),
            'sources'  => AttendanceSource::ordered()->get(),
            'defaultShift' => Shift::where('is_default', true)->where('is_active', true)->first(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $this->validatePayload($request);

        $settings = AttendanceSetting::current();
        $before   = $settings->only(array_keys($data));

        $data['updated_by'] = session('user.id');

        $defaultShift = Shift::where('is_default', true)->where('is_active', true)->first();

        // Shift default yang tidak lintas tengah malam wajib pulang lebih lambat
        // dari masuk; kalau tidak durasi kerjanya negatif.
        if ($defaultShift && !$defaultShift->crosses_midnight && $data['default_check_out'] <= $data['default_check_in']) {
            return back()->withInput()->withErrors([
                'default_check_out' => 'Default check-out must be later than default check-in. '
                    . 'To run a shift past midnight, edit the default shift and tick "Crosses midnight".',
            ]);
        }

        DB::transaction(function () use ($settings, $data, $defaultShift) {
            $settings->update($data);

            // Settings menyalin jam default ke shift default (satu sumber
            // kebenaran): tanpa ini, toleransi yang diisi di sini tidak pernah
            // dipakai karena presensi memakai tabel shift.
            $defaultShift?->update([
                'check_in_time'          => $data['default_check_in'],
                'check_out_time'         => $data['default_check_out'],
                'late_tolerance_minutes' => $data['default_late_tolerance_minutes'],
            ]);
        });
        AttendanceSetting::forgetCache();

        // Kebijakan presensi menentukan apakah seluruh karyawan dapat bekerja
        // hari itu. Perubahannya dicatat supaya bila besok ada keluhan massal,
        // penyebabnya dapat ditelusuri ke perubahan ini.
        Log::info('Attendance settings updated.', [
            'actor_id' => session('user.id'),
            'before'   => $before,
            'after'    => $settings->only(array_keys($before)),
        ]);

        return back()->with('success', 'Attendance settings saved.');
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'geofence_mode'                  => ['required', Rule::in(AttendanceSetting::GEOFENCE_MODES)],
            'max_shifts_per_employee'        => ['required', 'integer', 'between:1,10'],
            'min_accuracy_meters'            => ['required', 'integer', 'between:10,5000'],
            'default_check_in'               => ['required', 'date_format:H:i'],
            'default_check_out'              => ['required', 'date_format:H:i'],
            'default_late_tolerance_minutes' => ['required', 'integer', 'between:0,480'],
            'correction_max_days'            => ['required', 'integer', 'between:1,365'],
            'auto_close_hours'               => ['required', 'integer', 'between:1,48'],
            'require_location'               => ['nullable', 'boolean'],
            'show_location_diagnostic'       => ['nullable', 'boolean'],
            'allow_self_correction'          => ['nullable', 'boolean'],
        ], [
            'min_accuracy_meters.between'     => 'The accuracy threshold must be between 10 and 5000 meters.',
            'max_shifts_per_employee.between' => 'An employee may hold between 1 and 10 active shifts.',
            'correction_max_days.between' => 'The correction window must be between 1 and 365 days.',
        ]);

        $validated['require_location']      = $request->boolean('require_location');
        // Checkbox yang tidak dicentang TIDAK terkirim sama sekali - tanpa baris
        // ini, mematikan sakelarnya mustahil dari layar.
        $validated['show_location_diagnostic'] = $request->boolean('show_location_diagnostic');
        $validated['allow_self_correction'] = $request->boolean('allow_self_correction');

        return $validated;
    }
}
