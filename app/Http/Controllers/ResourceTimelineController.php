<?php

namespace App\Http\Controllers;

use App\Enums\HomeBase;
use App\Models\Customer;
use App\Models\Employee;
use App\Services\ResourceTimelineService;
use App\Support\SessionUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResourceTimelineController extends Controller
{
    private const MENU_SLUG = 'reporting.resource-timeline';

    public function __construct(private ResourceTimelineService $service)
    {
    }

    public function index()
    {
        $sessionUser = SessionUser::fromSession(session('user'));
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        return view('reporting.resource-timeline', [
            'user' => session('user'),
            'homeBaseOptions' => HomeBase::options(),
        ]);
    }

    public function customers()
    {
        $guard = $this->guard();
        if ($guard instanceof \Illuminate\Http\JsonResponse) {
            return $guard;
        }

        return response()->json([
            'success' => true,
            'data'    => $this->service->customerCodeOptions(),
        ]);
    }

    public function grid(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof \Illuminate\Http\JsonResponse) {
            return $guard;
        }

        try {
            $month    = $request->integer('month', now()->month);
            $year     = $request->integer('year', now()->year);
            $homeBase = $request->string('home_base')->toString() ?: null;

            if ($request->query('view') === 'yearly') {
                $grid = $this->service->buildYearGrid($year, $homeBase);

                return response()->json([
                    'success' => true,
                    'view'    => 'yearly',
                    'year'    => $year,
                    'rows'    => $grid['rows'],
                ]);
            }

            $grid = $this->service->buildGrid($month, $year, $homeBase);

            return response()->json([
                'success' => true,
                'view'    => 'monthly',
                'month'   => $month,
                'year'    => $year,
                'days'    => $grid['days'],
                'rows'    => $grid['rows'],
            ]);
        } catch (\Exception $e) {
            Log::error('ResourceTimeline@grid error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load timeline grid.'], 500);
        }
    }

    public function entries(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof \Illuminate\Http\JsonResponse) {
            return $guard;
        }

        try {
            $employeeId = $request->integer('employee_id');
            if (!$employeeId) {
                return response()->json(['success' => false, 'message' => 'employee_id is required.'], 422);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->service->collapseToRanges($employeeId),
            ]);
        } catch (\Exception $e) {
            Log::error('ResourceTimeline@entries error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load entries.'], 500);
        }
    }

    public function upsertEntries(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof \Illuminate\Http\JsonResponse) {
            return $guard;
        }

        try {
            $validated = $this->validateRange($request, [
                // Location = customer code dari master Business Partner, ATAU teks bebas
                // bila is_custom = true (customer di luar master).
                'is_custom'            => 'nullable|boolean',
                'location'             => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($request) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if ($request->boolean('is_custom')) {
                        // Karakter aman saja (huruf, angka, spasi, . , & ( ) / _ -) supaya
                        // tidak menyerupai payload dan terblokir WAF di edge.
                        if (!preg_match('/^[\p{L}\p{N} .,&()\/_\-]+$/u', $value)) {
                            $fail('Custom customer may only contain letters, numbers, spaces and . , & ( ) / _ -');
                        }
                        return;
                    }
                    if (!Customer::where('customer_code', $value)->exists()) {
                        $fail('Selected customer code is not in the master list. Use the custom customer field instead.');
                    }
                }],
                'previous_start_date'  => 'nullable|date',
                'previous_end_date'    => 'nullable|date|after_or_equal:previous_start_date',
                'previous_location'    => 'nullable|string|max:255',
            ]);

            $location = isset($validated['location']) ? preg_replace('/\s+/', ' ', trim($validated['location'])) : null;

            // Custom text that matches a master code (ignoring case) is stored as
            // the master code, so it does not create a look-alike duplicate.
            if ($location && $request->boolean('is_custom')) {
                $master = Customer::whereRaw('LOWER(customer_code) = ?', [mb_strtolower($location)])->value('customer_code');
                $location = $master ?: $location;
            }

            $this->service->upsertRange(
                $validated['employee_id'],
                $validated['start_date'],
                $validated['end_date'],
                $location,
                $validated['previous_start_date'] ?? null,
                $validated['previous_end_date'] ?? null,
                $validated['previous_location'] ?? null
            );

            return response()->json(['success' => true, 'message' => 'Timeline saved.']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timeline data is invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('ResourceTimeline@upsertEntries error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save timeline.'], 500);
        }
    }

    public function deleteEntries(Request $request)
    {
        $guard = $this->guard();
        if ($guard instanceof \Illuminate\Http\JsonResponse) {
            return $guard;
        }

        try {
            $validated = $this->validateRange($request, ['location' => 'nullable|string|max:255']);

            $this->service->deleteRange(
                $validated['employee_id'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['location'] ?? null
            );

            return response()->json(['success' => true, 'message' => 'Timeline cleared.']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timeline data is invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('ResourceTimeline@deleteEntries error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to clear timeline.'], 500);
        }
    }

    private function validateRange(Request $request, array $extra = []): array
    {
        return $request->validate(array_merge([
            'employee_id' => [
                'required',
                'integer',
                'exists:employee,employee_id',
                function ($attribute, $value, $fail) {
                    $isConsultant = $this->service->consultantsQuery()->where('employee.employee_id', $value)->exists();
                    if (!$isConsultant) {
                        $fail('Selected employee is not an SAP Consultant, Salesforce Consultant or Project Management Officer.');
                    }
                },
            ],
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ], $extra));
    }

    private function guard()
    {
        $sessionUser = SessionUser::fromSession(session('user'));
        if (!$sessionUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $employee = Employee::find($sessionUser->id);
        if (!$employee || !$employee->canAccessMenu(self::MENU_SLUG)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return $employee;
    }
}
