<?php

namespace Tests\Unit\Contracts;

use App\Models\ContractTemplate;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\ContractTemplateTransfer as T;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Ekspor / impor Template kontrak: berkas JSON, analisis tanpa menulis, penerapan lewat saveTemplate, bawaan sistem. */
class ContractTemplateTransferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded (run: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit).');
        }
        Schema::create('contract_templates', function ($t) {
            $t->id();
            $t->string('name', 150); $t->string('contract_type', 20); $t->string('position')->nullable(); $t->string('position_type', 100)->nullable();
            $t->string('description', 500)->nullable(); $t->longText('body_html'); $t->boolean('use_letterhead')->default(true);
            $t->unsignedBigInteger('signatory_employee_id')->nullable(); $t->unsignedBigInteger('approver_employee_id')->nullable();
            $t->string('page_size', 10)->nullable(); $t->string('page_orientation', 10)->nullable();
            $t->decimal('margin_top', 5, 2)->nullable(); $t->decimal('margin_right', 5, 2)->nullable(); $t->decimal('margin_bottom', 5, 2)->nullable(); $t->decimal('margin_left', 5, 2)->nullable();
            $t->boolean('is_system_default')->default(false); $t->string('status', 20)->default('active');
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('employee', function ($t) { $t->unsignedBigInteger('employee_id')->primary(); $t->string('eci', 20); });
        // Catatan audit tidak relevan di sini (tabelnya tidak dibuat).
        ContractTemplate::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        ContractTemplate::setEventDispatcher(app('events'));
        parent::tearDown();
    }

    private function svc(array $signers = []): T
    {
        return new T(new ContractService(), fn () => $signers);
    }

    private function item(array $over = []): array
    {
        return $over + [
            'name' => 'Standard PKWT', 'contract_type' => 'PKWT', 'position' => null, 'position_type' => null, 'description' => 'Baseline',
            'status' => 'active', 'is_system_default' => false, 'use_letterhead' => true,
            'page' => ['size' => 'A4', 'orientation' => 'portrait', 'margins' => ['top' => null, 'right' => null, 'bottom' => null, 'left' => null]],
            'signatory' => null, 'approver' => null,
            'body_html' => '<p>Kontrak {{nomor_kontrak}} untuk {{nama_karyawan}}.</p>',
        ];
    }

    private function bundle(array $templates): string
    {
        return json_encode(['format' => T::FORMAT, 'version' => 1, 'templates' => $templates]);
    }

    private function make(array $over = []): ContractTemplate
    {
        return $this->svc()->apply([$this->item($over)], [0 => 'create'], 1, true) ? ContractTemplate::latest('id')->first() : throw new \RuntimeException();
    }

    public function test_parse_rejects_files_that_are_not_a_template_bundle(): void
    {
        foreach (['not json', '{"format":"other","templates":[{}]}', json_encode(['format' => T::FORMAT, 'version' => 99, 'templates' => [[]]]), json_encode(['format' => T::FORMAT, 'version' => 1, 'templates' => []])] as $raw) {
            try {
                $this->svc()->parse($raw);
                $this->fail('Expected a DomainException for: ' . $raw);
            } catch (\DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        $this->assertCount(1, $this->svc()->parse("\xEF\xBB\xBF" . $this->bundle([$this->item()])), 'BOM di awal berkas diabaikan');
    }

    public function test_parse_refuses_too_many_templates_and_oversized_files(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc()->parse($this->bundle(array_fill(0, T::MAX_TEMPLATES + 1, $this->item())));
    }

    public function test_export_then_import_recreates_templates_with_the_same_text_and_page_settings(): void
    {
        $a = $this->make(['name' => 'A', 'page' => ['size' => 'A4', 'orientation' => 'landscape', 'margins' => ['top' => 2, 'right' => 2.5, 'bottom' => 2, 'left' => 2.5]], 'status' => 'inactive']);
        $this->make(['name' => 'B', 'contract_type' => 'EXTERNAL', 'body_html' => '<p>PKS {{nomor_kontrak}} {{nama_konsultan}}</p>']);

        $raw = json_encode((new T(new ContractService()))->export(ContractTemplate::orderBy('id')->get()));
        $this->assertSame(2, json_decode($raw, true)['count']);
        $body = $a->body_html;

        DB::table('contract_templates')->delete();   // seperti database yang dibersihkan
        $items = $this->svc()->parse($raw);
        $analysis = $this->svc()->analyze($items);
        $this->assertSame(['create', 'create'], array_column($analysis, 'default'));
        $r = $this->svc()->apply($items, [0 => 'create', 1 => 'create'], 7, true);

        $this->assertSame(2, $r['created']);
        $back = ContractTemplate::where('name', 'A')->first();
        $this->assertSame($body, $back->body_html);
        $this->assertSame('landscape', $back->page_orientation);
        $this->assertEquals(2.5, (float) $back->margin_left);
        $this->assertSame('inactive', $back->status);
        $this->assertSame(7, (int) $back->created_by);
        $this->assertSame('EXTERNAL', ContractTemplate::where('name', 'B')->value('contract_type'));
    }

    public function test_analysis_finds_existing_identical_and_changed_templates_without_writing(): void
    {
        $this->make();
        $before = ContractTemplate::count();

        $same = $this->svc()->analyze([$this->item()])[0];
        $this->assertTrue($same['identical']);
        $this->assertSame('skip', $same['default']);
        $this->assertSame(['update', 'copy', 'skip'], $same['options']);

        $changed = $this->svc()->analyze([$this->item(['body_html' => '<p>Teks baru {{nomor_kontrak}}</p>', 'name' => ' standard pkwt '])])[0];
        $this->assertFalse($changed['identical']);
        $this->assertSame('update', $changed['default'], 'nama dicocokkan tanpa memperhatikan huruf besar/kecil dan spasi');
        $this->assertNotNull($changed['match']);
        $this->assertSame($before, ContractTemplate::count());
    }

    public function test_problems_are_reported_per_template_and_block_only_that_template(): void
    {
        $items = [
            $this->item(['name' => 'Bad placeholder', 'body_html' => '<p>{{tidak_ada}} dan {{nomor_kontrak}}</p>']),
            $this->item(['name' => 'Bad type', 'contract_type' => 'WHATEVER']),
            $this->item(['name' => 'Empty', 'body_html' => '<p> </p>']),
            $this->item(['name' => '']),
            $this->item(['name' => 'Fine']),
        ];
        $a = $this->svc()->analyze($items);

        $this->assertStringContainsString('{{tidak_ada}}', $a[0]['errors'][0]);
        $this->assertStringContainsString('Unknown contract type', $a[1]['errors'][0]);
        $this->assertNotEmpty($a[2]['errors']);
        $this->assertNotEmpty($a[3]['errors']);
        $this->assertSame([], $a[4]['errors']);
        $this->assertSame(['skip'], $a[0]['options']);

        $r = $this->svc()->apply($items, [0 => 'create', 1 => 'create', 2 => 'create', 3 => 'create', 4 => 'create'], 1, true);
        $this->assertSame(1, $r['created']);
        $this->assertSame(4, $r['failed']);
        $this->assertSame(['Fine'], ContractTemplate::pluck('name')->all());
    }

    public function test_scripts_and_event_handlers_are_stripped_like_in_the_editor(): void
    {
        $r = $this->svc()->apply([$this->item(['body_html' => '<p onclick="x()">Halo {{nomor_kontrak}}</p><script>alert(1)</script>'])], [0 => 'create'], 1, true);
        $this->assertSame(1, $r['created']);
        $body = ContractTemplate::first()->body_html;
        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringContainsString('{{nomor_kontrak}}', $body);
    }

    public function test_update_overwrites_the_match_and_needs_the_edit_permission(): void
    {
        $t = $this->make();
        $new = $this->item(['body_html' => '<p>Versi baru {{nomor_kontrak}}</p>']);

        $denied = $this->svc()->apply([$new], [0 => 'update'], 1, false);
        $this->assertSame(1, $denied['failed']);
        $this->assertStringContainsString('Edit permission', $denied['rows'][0]['message']);
        $this->assertStringNotContainsString('Versi baru', $t->fresh()->body_html);

        $ok = $this->svc()->apply([$new], [0 => 'update'], 9, true);
        $this->assertSame(1, $ok['updated']);
        $this->assertSame(1, ContractTemplate::count(), 'update tidak membuat salinan');
        $this->assertStringContainsString('Versi baru', $t->fresh()->body_html);
    }

    public function test_choosing_create_when_a_match_exists_fails_and_copy_adds_a_named_copy(): void
    {
        $this->make();
        $r = $this->svc()->apply([$this->item()], [0 => 'create'], 1, true);
        $this->assertSame(1, $r['failed']);
        $this->assertSame(1, ContractTemplate::count());

        $c = $this->svc()->apply([$this->item()], [0 => 'copy'], 1, true);
        $this->assertSame(1, $c['created']);
        $this->assertSame(2, ContractTemplate::count());
        $this->assertStringContainsString('(imported ', ContractTemplate::orderByDesc('id')->value('name'));
    }

    public function test_no_decision_means_skip(): void
    {
        $r = $this->svc()->apply([$this->item()], [], 1, true);
        $this->assertSame(1, $r['skipped']);
        $this->assertSame(0, ContractTemplate::count());
    }

    public function test_a_system_default_is_restored_only_when_none_exists_for_its_type(): void
    {
        $sys = $this->item(['name' => 'System PKWT', 'is_system_default' => true]);
        $a = $this->svc()->analyze([$sys])[0];
        $this->assertTrue((bool) array_filter($a['warnings'], fn ($w) => str_contains($w, 'system default')));

        $r = $this->svc()->apply([$sys], [0 => 'create'], 1, true);
        $this->assertSame(1, $r['created']);
        $this->assertTrue((bool) ContractTemplate::first()->is_system_default);

        // Sudah ada bawaan sistem untuk jenis itu: yang kedua diimpor sebagai template biasa.
        $b = $this->item(['name' => 'Another PKWT', 'is_system_default' => true]);
        $this->assertSame([], array_filter($this->svc()->analyze([$b])[0]['warnings'], fn ($w) => str_contains($w, 'system default')));
        $this->svc()->apply([$b], [0 => 'create'], 1, true);
        $this->assertFalse((bool) ContractTemplate::where('name', 'Another PKWT')->value('is_system_default'));
    }

    public function test_signers_are_matched_by_eci_and_missing_ones_only_warn(): void
    {
        DB::table('employee')->insert([['employee_id' => 5, 'eci' => 'K19002'], ['employee_id' => 6, 'eci' => 'X00001']]);
        $svc = $this->svc([5]);
        $a = $svc->analyze([$this->item(['signatory' => ['eci' => 'K19002'], 'approver' => ['eci' => 'X00001']]), $this->item(['name' => 'Ghost', 'signatory' => ['eci' => 'NOPE']])]);

        $this->assertSame([], $a[0]['errors']);
        $this->assertTrue((bool) array_filter($a[0]['warnings'], fn ($w) => str_contains($w, 'not a signer')), 'ada tetapi bukan penandatangan');
        $this->assertTrue((bool) array_filter($a[1]['warnings'], fn ($w) => str_contains($w, 'does not exist')));
    }

    public function test_html_export_round_trips_and_an_edited_body_is_read_back(): void
    {
        $t = $this->make(['name' => 'Ke <HTML> & "uji"', 'description' => 'Pakai </script> aneh', 'page' => ['size' => 'A4', 'orientation' => 'landscape', 'margins' => ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2]]]);
        $html = $this->svc()->exportHtml($t);

        $this->assertStringContainsString('ec-body-start', $html);
        $this->assertSame(1, substr_count(strtolower($html), '</script>'), 'JSON di dalam <script> tidak boleh menutup tag lebih awal');

        $items = $this->svc()->parse($html);
        $this->assertCount(1, $items);
        $this->assertSame('Ke <HTML> & "uji"', $items[0]['name']);
        $this->assertSame('landscape', $items[0]['page']['orientation']);
        $a = $this->svc()->analyze($items);
        $this->assertSame([], $a[0]['errors']);
        $this->assertTrue($a[0]['identical'], 'berkas HTML yang belum disunting sama persis dengan template tersimpan');

        // Disunting di editor: teks diganti, penanda tetap.
        $edited = preg_replace('~(<!-- ec-body-start -->).*(<!-- ec-body-end -->)~s', '$1<p>Revisi {{nomor_kontrak}} oleh {{nama_karyawan}}</p>$2', $html);
        $this->assertNotNull($edited);
        $items = $this->svc()->parse($edited);
        $a = $this->svc()->analyze($items);
        $this->assertFalse($a[0]['identical']);
        $this->assertSame('update', $a[0]['default']);
        $this->svc()->apply($items, [0 => 'update'], 3, true);
        $this->assertStringContainsString('Revisi', ContractTemplate::find($t->id)->body_html);
    }

    public function test_html_without_details_a_pdf_and_scripts_in_html_are_handled(): void
    {
        foreach ([
            '<html><body><p>Hanya teks</p></body></html>' => 'no template details',
            "%PDF-1.4\n%binary" => 'PDF',
        ] as $raw => $needle) {
            try {
                $this->svc()->parse($raw);
                $this->fail('Expected DomainException');
            } catch (\DomainException $e) {
                $this->assertStringContainsString($needle, $e->getMessage());
            }
        }

        $meta = json_encode(['format' => T::FORMAT, 'version' => 1, 'name' => 'Nakal', 'contract_type' => 'PKWT']);
        $html = '<html><head><script type="application/json" id="ec-template">' . $meta . '</script></head><body><!-- ec-body-start --><p onclick="x()">Isi {{nomor_kontrak}}</p><script>alert(1)</script><!-- ec-body-end --></body></html>';
        $a = $this->svc()->analyze($this->svc()->parse($html));
        $this->assertSame([], $a[0]['errors']);
        $r = $this->svc()->apply($this->svc()->parse($html), [0 => 'create'], 1, true);
        $this->assertSame(1, $r['created']);
        $body = ContractTemplate::where('name', 'Nakal')->value('body_html');
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('alert', $body);

        // Tanpa penanda: isi <body> dipakai.
        $noMarkers = '<html><head><script id="ec-template" type="application/json">' . $meta . '</script></head><body><p>Isi {{nomor_kontrak}}</p></body></html>';
        $this->assertStringContainsString('Isi', $this->svc()->parse($noMarkers)[0]['body_html']);
    }
}
