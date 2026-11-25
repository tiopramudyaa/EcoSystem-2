<?php

namespace App\Services\Contracts;

use App\Services\External\ExternalContractService;
use App\Services\Management\EmployeeMasterData;
use Illuminate\Support\Facades\Cache;

/**
 * Banner "kontrak akan berakhir" di layout dashboard. Siapa yang melihatnya ditentukan oleh izin menu
 * (config hc_contract.expiry_alert.audiences), jadi role baru yang diberi menu Contract otomatis ikut.
 */
class ContractExpiryAlert
{
    public function __construct(private ContractService $contracts, private ExternalContractService $external) {}

    /**
     * @param  callable(string):bool  $canView  izin menu: fn (slug) => bool
     * @return array{days:int,total:int,items:array<int,array{label:string,count:int,url:string}>}|null
     */
    public function for(callable $canView): ?array
    {
        $cfg = config('hc_contract.expiry_alert');
        if (!($cfg['enabled'] ?? false)) {
            return null;
        }

        $days = max((int) ($cfg['min_days'] ?? 30), (int) EmployeeMasterData::rule('contract', 'ending_soon_days'));
        $today = now()->toDateString();
        $until = now()->addDays($days)->toDateString();

        $items = [];
        foreach ($cfg['audiences'] as $scope => $a) {
            if (!$canView($a['slug'])) {
                continue;
            }
            $count = Cache::remember("contract_expiry_alert:{$scope}:{$days}:{$today}", (int) ($cfg['cache_secs'] ?? 600), fn () => $scope === 'internal'
                ? $this->contracts->endingBetween($today, $until)
                : $this->external->endingBetween($today, $until));
            if ($count > 0) {
                $items[] = [
                    'scope' => $scope,
                    'count' => $count,
                    'url'   => $scope === 'internal'
                        ? route($a['route'], ['status' => 'active', 'end_from' => $today, 'end_to' => $until])
                        : route($a['route']),
                ];
            }
        }

        $total = array_sum(array_column($items, 'count'));

        return $total > 0 ? ['days' => $days, 'total' => $total, 'items' => $items] : null;
    }
}
