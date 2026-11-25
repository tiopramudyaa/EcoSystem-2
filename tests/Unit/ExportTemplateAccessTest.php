<?php

namespace Tests\Unit;

use App\Enums\RoleId;
use App\Http\Controllers\ExportTemplateController;
use App\Models\ExportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Template export Personal dan Global: siapa melihat, menimpa, dan menghapus apa.
 *
 * Berjalan di SQLite in-memory (lihat phpunit.xml), jadi tidak menyentuh database
 * kerja. Tabel export_templates dibuat dari migrasi aslinya; tabel karyawan dibuat
 * minimal seperlunya.
 */
class ExportTemplateAccessTest extends TestCase
{
    private const ADMIN = 1;
    private const ALICE = 2;
    private const BOB   = 3;

    private ExportTemplateController $c;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('employee', function ($t) {
            $t->id('employee_id');
            $t->string('eci')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('employee_basic_data', function ($t) {
            $t->id('basic_data_id');
            $t->unsignedBigInteger('employee_id');
            $t->string('first_name')->nullable();
            $t->string('nick_name')->nullable();
        });
        Schema::create('employee_role', function ($t) {
            $t->unsignedBigInteger('id')->primary();
        });
        Schema::create('employee_role_assignment', function ($t) {
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('role_id');
            $t->timestamps();
        });

        foreach (['2026_11_25_000002_create_export_templates_table', '2026_11_25_000003_add_is_global_to_export_templates'] as $file) {
            (require database_path("migrations/{$file}.php"))->up();
        }

        foreach ([self::ADMIN => 'Root', self::ALICE => 'Alice', self::BOB => 'Bob'] as $id => $name) {
            DB::table('employee')->insert(['employee_id' => $id]);
            DB::table('employee_basic_data')->insert(['employee_id' => $id, 'first_name' => $name, 'nick_name' => $name]);
        }
        DB::table('employee_role')->insert(['id' => RoleId::EC_ADMINISTRATOR->value]);
        DB::table('employee_role_assignment')->insert(['employee_id' => self::ADMIN, 'role_id' => RoleId::EC_ADMINISTRATOR->value]);

        $this->c = new ExportTemplateController();
    }

    private function as(?int $employeeId): void
    {
        session()->flush();
        if ($employeeId) {
            session(['user' => ['id' => $employeeId]]);
        }
    }

    private function save(string $name, string $scope, array $columns = ['Tiket', 'Description'], string $type = 'ticket'): \Illuminate\Http\JsonResponse
    {
        return $this->c->store(Request::create('/x', 'POST', ['name' => $name, 'scope' => $scope, 'columns' => $columns]), $type);
    }

    /** @return array<int, array> */
    private function listFor(?int $employeeId, string $type = 'ticket'): array
    {
        $this->as($employeeId);

        return json_decode($this->c->index($type)->getContent(), true)['templates'];
    }

    private function names(array $templates): array
    {
        return array_map(fn ($t) => $t['name'] . ':' . $t['scope'], $templates);
    }

    public function test_personal_template_is_visible_only_to_its_owner(): void
    {
        $this->as(self::ALICE);
        $this->save('Mine', 'personal');

        $this->assertSame(['Mine:personal'], $this->names($this->listFor(self::ALICE)));
        $this->assertSame([], $this->listFor(self::BOB));
        $this->assertSame([], $this->listFor(self::ADMIN), 'even an administrator does not see someone else\'s personal template');
    }

    public function test_global_template_is_visible_to_everyone_with_owner_name_and_manage_flag(): void
    {
        $this->as(self::ALICE);
        $this->save('Shared', 'global');

        $mine = $this->listFor(self::ALICE)[0];
        $this->assertSame('You', $mine['owner_name']);
        $this->assertTrue($mine['can_manage']);

        $theirs = $this->listFor(self::BOB)[0];
        $this->assertSame('Shared', $theirs['name']);
        $this->assertSame('global', $theirs['scope']);
        $this->assertSame('Alice', $theirs['owner_name']);
        $this->assertFalse($theirs['can_manage']);
        $this->assertSame(['Tiket', 'Description'], $theirs['columns']);
    }

    public function test_templates_are_listed_per_export_type(): void
    {
        $this->as(self::ALICE);
        $this->save('Shared', 'global');

        $this->assertSame([], $this->listFor(self::BOB, 'employee'));
    }

    public function test_personal_comes_before_global_then_by_name(): void
    {
        $this->as(self::BOB);
        $this->save('Zeta', 'personal');
        $this->save('Alpha', 'global');
        $this->as(self::ALICE);
        $this->save('Beta', 'global');

        $this->assertSame(['Zeta:personal', 'Alpha:global', 'Beta:global'], $this->names($this->listFor(self::BOB)));
    }

    public function test_global_name_must_be_unique_across_owners_but_personal_may_reuse_it(): void
    {
        $this->as(self::ALICE);
        $this->save('Shared', 'global');

        $this->as(self::BOB);
        $clash = $this->save('Shared', 'global');
        $this->assertSame(422, $clash->getStatusCode());
        $this->assertStringContainsString('already exists', json_decode($clash->getContent(), true)['message']);

        $this->assertSame(200, $this->save('Shared', 'personal')->getStatusCode());
        $this->assertSame(2, ExportTemplate::where('name', 'Shared')->count());
    }

    public function test_saving_the_same_name_replaces_it_and_can_switch_scope(): void
    {
        $this->as(self::ALICE);
        $this->save('Mine', 'personal', ['Tiket']);
        $response = $this->save('Mine', 'global', ['Tiket', 'Customer']);

        $this->assertSame('global', json_decode($response->getContent(), true)['template']['scope']);
        $this->assertSame(1, ExportTemplate::where('employee_id', self::ALICE)->where('name', 'Mine')->count());
        $this->assertSame(['Tiket', 'Customer'], ExportTemplate::first()->columns);
        $this->assertTrue(ExportTemplate::first()->is_global);
    }

    public function test_only_the_owner_can_delete_a_global_template(): void
    {
        $this->as(self::ALICE);
        $id = ExportTemplate::find(json_decode($this->save('Shared', 'global')->getContent(), true)['template']['id'])->id;

        $this->as(self::BOB);
        $this->assertSame(403, $this->c->destroy($id)->getStatusCode());
        $this->assertSame(1, ExportTemplate::count());

        $this->as(self::ALICE);
        $this->assertSame(200, $this->c->destroy($id)->getStatusCode());
        $this->assertSame(0, ExportTemplate::count());
    }

    public function test_someone_elses_personal_template_cannot_be_found_or_deleted(): void
    {
        $this->as(self::ALICE);
        $id = json_decode($this->save('Mine', 'personal')->getContent(), true)['template']['id'];

        foreach ([self::BOB, self::ADMIN] as $other) {
            $this->as($other);
            $this->assertSame(404, $this->c->destroy($id)->getStatusCode());
        }
        $this->assertSame(1, ExportTemplate::count());
    }

    public function test_administrator_can_delete_any_global_template(): void
    {
        $this->as(self::ALICE);
        $id = json_decode($this->save('Shared', 'global')->getContent(), true)['template']['id'];

        $this->assertTrue($this->listFor(self::ADMIN)[0]['can_manage']);

        $this->as(self::ADMIN);
        $this->assertSame(200, $this->c->destroy($id)->getStatusCode());
        $this->assertSame(0, ExportTemplate::count());
    }

    public function test_unknown_columns_are_dropped_and_all_invalid_is_rejected(): void
    {
        $this->as(self::ALICE);
        $ok = json_decode($this->save('Mixed', 'personal', ['Description', 'Nope', 'Tiket'])->getContent(), true);
        $this->assertSame(['Tiket', 'Description'], $ok['template']['columns'], 'valid columns only, in file order');

        $this->assertSame(422, $this->save('Bad', 'personal', ['Nope'])->getStatusCode());
    }

    public function test_columns_removed_from_the_export_later_disappear_from_saved_templates(): void
    {
        ExportTemplate::create([
            'employee_id' => self::ALICE, 'export_type' => 'ticket', 'name' => 'Old', 'is_global' => true,
            'columns' => ['Tiket', 'A Column That Was Removed'],
        ]);

        $this->assertSame(['Tiket'], $this->listFor(self::BOB)[0]['columns']);
    }

    public function test_signed_out_requests_are_refused_and_unknown_type_is_not_found(): void
    {
        $this->as(null);
        $this->assertSame(401, $this->c->index('ticket')->getStatusCode());
        $this->assertSame(401, $this->save('X', 'personal')->getStatusCode());
        $this->assertSame(401, $this->c->destroy(1)->getStatusCode());

        $this->as(self::ALICE);
        $this->expectException(NotFoundHttpException::class);
        $this->c->index('not_a_real_type');
    }
}
