<?php

namespace Tests\Unit;

use App\Http\Controllers\DeliveryProjectController;
use App\Models\DeliveryProject;
use App\Models\DeliveryProjectDocumentFolder as Folder;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Folder dokumen Delivery Project (section Project Documents): folder standar,
 * penomoran folder tambahan 11, 12, ..., dan aturan rename/hapus.
 *
 * SQLite in-memory (phpunit.xml). Project tidak punya folder OneDrive, jadi
 * tidak ada panggilan ke OneDrive.
 */
class DeliveryProjectDocumentFolderTest extends TestCase
{
    private DeliveryProjectController $c;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('delivery_projects', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('onedrive_folder_id')->nullable();
            $t->timestamp('last_activity_at')->nullable();
            $t->timestamps();
        });
        Schema::create('documents', function ($t) {
            $t->id();
            $t->unsignedBigInteger('delivery_projects_id');
            $t->string('document_name');
            $t->string('link_document')->nullable();
            $t->string('document_type', 100);
            $t->timestamps();
        });

        DB::table('delivery_projects')->insert([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
        DB::table('documents')->insert([
            'delivery_projects_id' => 1, 'document_name' => 'Old', 'document_type' => 'PO',
        ]);

        (require database_path('migrations/2026_11_25_000004_create_delivery_project_document_folders_table.php'))->up();

        $this->c = new DeliveryProjectController();
    }

    private function project(int $id = 1): DeliveryProject
    {
        return DeliveryProject::findOrFail($id);
    }

    private function req(array $data): Request
    {
        $r = Request::create('/x', 'POST', $data);
        $r->headers->set('Accept', 'application/json');
        return $r;
    }

    private function addFolder(string $name, int $projectId = 1): array
    {
        return $this->c->storeDocumentFolder($this->req(['name' => $name]), $this->project($projectId))->getData(true);
    }

    public function test_migration_creates_standard_folders_and_moves_old_documents_to_other(): void
    {
        $names = Folder::where('delivery_projects_id', 1)->ordered()->get()->map->display_name->all();
        $this->assertSame(
            ['01. Discover', '02. Prepare', '03. Explore', '04. Realize', '05. Deploy', '06. Run', '00. Other'],
            $names
        );
        $this->assertSame(7, Folder::where('delivery_projects_id', 2)->count());
        $this->assertSame('00. Other', Document::first()->folder->display_name);
    }

    public function test_ensure_defaults_is_idempotent_and_files_unfoldered_documents_under_other(): void
    {
        DB::table('documents')->insert(['delivery_projects_id' => 2, 'document_name' => 'New', 'document_type' => 'KAK']);

        Folder::ensureDefaults($this->project(2));
        Folder::ensureDefaults($this->project(2));

        $this->assertSame(7, Folder::where('delivery_projects_id', 2)->count());
        $this->assertSame(0, Document::whereNull('folder_id')->count());
    }

    public function test_custom_folders_are_numbered_from_11_per_project(): void
    {
        $this->assertSame('11. Add', $this->addFolder('Add')['folder']['display_name']);
        $second = $this->addFolder('  Change Request ');
        $this->assertSame('12. Change Request', $second['folder']['display_name']);
        $this->assertSame(13, $second['next_number']);

        // Penomoran per project.
        $this->assertSame('11. Other Project', $this->addFolder('Other Project', 2)['folder']['display_name']);

        // Custom folders are listed after 00. Other.
        $last = Folder::where('delivery_projects_id', 1)->ordered()->get()->last();
        $this->assertSame('12. Change Request', $last->display_name);
    }

    public function test_folder_name_rules(): void
    {
        $this->addFolder('Add');

        foreach (['add', 'Discover', '12. Numbered', 'a/b', ''] as $bad) {
            try {
                $this->addFolder($bad);
                $this->fail("Name '{$bad}' should be rejected");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('name', $e->errors());
            }
        }
    }

    public function test_standard_folders_cannot_be_renamed_or_deleted(): void
    {
        $discover = Folder::where('delivery_projects_id', 1)->where('number', 1)->first();

        $this->assertSame(422, $this->c->updateDocumentFolder($this->req(['name' => 'X']), $this->project(), $discover)->getStatusCode());
        $this->assertSame(422, $this->c->destroyDocumentFolder($this->project(), $discover)->getStatusCode());
    }

    public function test_custom_folder_can_be_renamed_and_deleted_only_when_empty(): void
    {
        $id     = $this->addFolder('Add')['folder']['id'];
        $folder = Folder::find($id);

        $renamed = $this->c->updateDocumentFolder($this->req(['name' => 'Addendum']), $this->project(), $folder)->getData(true);
        $this->assertSame('11. Addendum', $renamed['folder']['display_name']);

        $doc = $this->project()->documents()->create(['folder_id' => $id, 'document_name' => 'x', 'document_type' => 'PO']);
        $this->assertSame(422, $this->c->destroyDocumentFolder($this->project(), $folder->fresh())->getStatusCode());

        $doc->delete();
        $this->assertSame(200, $this->c->destroyDocumentFolder($this->project(), $folder->fresh())->getStatusCode());
        $this->assertNull(Folder::find($id));
    }

    public function test_folder_of_another_project_is_rejected(): void
    {
        $foreign = Folder::where('delivery_projects_id', 2)->first();

        $this->expectException(NotFoundHttpException::class);
        $this->c->destroyDocumentFolder($this->project(1), $foreign);
    }

    public function test_document_cannot_be_put_in_a_folder_of_another_project(): void
    {
        $foreign = Folder::where('delivery_projects_id', 2)->first();

        $this->expectException(ValidationException::class);
        $this->c->storeDocument($this->req([
            'document_name' => 'x', 'link_document' => 'https://example.com/x', 'document_type' => 'PO', 'folder_id' => $foreign->id,
        ]), $this->project(1));
    }

    /** OneDrive tiruan: menyimpan pohon folder di memori. */
    private function fakeOneDrive(array $children, array $existing): \App\Services\OneDriveService
    {
        return new class($children, $existing) extends \App\Services\OneDriveService {
            public array $moves = [];
            public array $created = [];
            public function __construct(public array $children, public array $existing) {}
            public function itemExists(string $itemId): bool { return in_array($itemId, $this->existing, true); }
            public function listSubFoldersByParentId(string $parentFolderId): array { return $this->children[$parentFolderId] ?? []; }
            public function findOrCreateSubFolderById(string $parentFolderId, string $folderName): string
            {
                foreach ($this->listSubFoldersByParentId($parentFolderId) as $c) {
                    if (mb_strtolower($c['name']) === mb_strtolower($folderName)) return $c['id'];
                }
                return $this->createSubFolder($parentFolderId, $folderName);
            }
            public function createSubFolder(string $parentFolderId, string $folderName): string
            {
                $id = 'new-' . count($this->created);
                $this->created[] = [$parentFolderId, $folderName];
                $this->children[$parentFolderId][] = ['id' => $id, 'name' => $folderName];
                $this->existing[] = $id;
                return $id;
            }
            public function moveItem(string $itemId, string $newParentId, ?string $newName = null): void { $this->moves[] = [$itemId, $newParentId]; }
        };
    }

    public function test_legacy_plan_cost_folder_is_moved_into_discover(): void
    {
        DB::table('delivery_projects')->where('id', 1)->update(['onedrive_folder_id' => 'root']);
        $od = $this->fakeOneDrive(['root' => [['id' => 'pc-old', 'name' => 'Plan Cost']]], ['root', 'pc-old']);

        $id = (new \App\Services\ProjectDocumentFolderService($od))->planCostFolderId($this->project());

        $this->assertSame('pc-old', $id);
        $this->assertSame([['root', '01. Discover']], $od->created);
        $this->assertSame([['pc-old', 'new-0']], $od->moves);
        $this->assertSame('new-0', Folder::where('delivery_projects_id', 1)->where('number', 1)->value('onedrive_folder_id'));
    }

    public function test_plan_cost_is_created_inside_discover_for_new_projects_and_stale_ids_are_recreated(): void
    {
        DB::table('delivery_projects')->where('id', 1)->update(['onedrive_folder_id' => 'root']);
        // ID Discover tersimpan sudah dihapus di OneDrive → dibuat ulang.
        Folder::where('delivery_projects_id', 1)->where('number', 1)->update(['onedrive_folder_id' => 'gone']);
        $od = $this->fakeOneDrive([], ['root']);

        $id = (new \App\Services\ProjectDocumentFolderService($od))->planCostFolderId($this->project());

        $this->assertSame([['root', '01. Discover'], ['new-0', 'Plan Cost']], $od->created);
        $this->assertSame('new-1', $id);
        $this->assertSame([], $od->moves);
    }

    public function test_document_without_folder_goes_to_other_and_can_move_folder(): void
    {
        $this->c->storeDocument($this->req([
            'document_name' => 'Link', 'link_document' => 'https://example.com/x', 'document_type' => 'PO',
        ]), $this->project(1));

        $doc = Document::where('document_name', 'Link')->first();
        $this->assertSame('00. Other', $doc->folder->display_name);

        $discover = Folder::where('delivery_projects_id', 1)->where('number', 1)->first();
        $r = Request::create('/x', 'PATCH', ['document_name' => 'Link', 'document_type' => 'PO', 'folder_id' => $discover->id]);
        $r->headers->set('Accept', 'application/json');
        $this->c->updateDocument($r, $doc);

        $this->assertSame('01. Discover', $doc->fresh()->folder->display_name);
    }
}
