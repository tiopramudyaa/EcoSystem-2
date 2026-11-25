@php
    $expiryAlert = ($user['type'] ?? session('user.type')) === 'employee' && isset($canDo)
        ? app(\App\Services\Contracts\ContractExpiryAlert::class)->for(fn (string $slug) => $canDo($slug))
        : null;
    $expiryKey = $expiryAlert ? 'expiryAlert:' . now()->toDateString() . ':' . $expiryAlert['total'] : '';
@endphp
@if($expiryAlert)
    <div id="contractExpiryAlert" role="alert" style="display:none" class="mb-4 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
        <i class="fas fa-triangle-exclamation text-amber-600"></i>
        <p class="flex-1 min-w-0">
            @foreach($expiryAlert['items'] as $item)
                <strong>{{ $item['count'] }} kontrak {{ $item['scope'] === 'external' ? 'konsultan' : 'karyawan' }}</strong> akan berakhir dalam {{ $expiryAlert['days'] }} hari.
                <a href="{{ $item['url'] }}" class="font-bold underline whitespace-nowrap">Lihat detail →</a>
                @if(!$loop->last)<span class="mx-1 text-amber-400">|</span>@endif
            @endforeach
        </p>
        <button type="button" id="contractExpiryClose" class="text-amber-700 hover:text-amber-900" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <script>
        (function () {
            const box = document.getElementById('contractExpiryAlert'), key = @json($expiryKey);
            try { if (sessionStorage.getItem(key)) return; } catch (e) {}
            box.style.display = '';
            document.getElementById('contractExpiryClose').addEventListener('click', () => {
                box.style.display = 'none';
                try { sessionStorage.setItem(key, '1'); } catch (e) {}
            });
        })();
    </script>
@endif
