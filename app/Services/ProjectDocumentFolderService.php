<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\DeliveryProjectDocumentFolder;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi folder dokumen Delivery Project ke OneDrive.
 *
 * Struktur: DELIVERY PROJECT / {Customer} / {Project} / {NN. Nama Folder} / file
 * Plan Cost : DELIVERY PROJECT / {Customer} / {Project} / 01. Discover / Plan Cost
 *
 * Subfolder dibuat saat folder OneDrive project dibuat (syncAll) dan, untuk
 * project lama atau bila pembuatan awal gagal, paling lambat saat upload pertama
 * ke folder tersebut (ensureOneDriveFolder).
 */
class ProjectDocumentFolderService
{
    public function __construct(private ?OneDriveService $oneDrive = null)
    {
    }

    private function od(): OneDriveService
    {
        return $this->oneDrive ??= new OneDriveService();
    }

    /**
     * Item ID subfolder OneDrive untuk folder dokumen ini; dibuat bila belum ada
     * atau bila ID tersimpan sudah tidak valid (folder dihapus manual di OneDrive).
     */
    public function ensureOneDriveFolder(DeliveryProjectDocumentFolder $folder, ?DeliveryProject $project = null): string
    {
        $project ??= $folder->project;

        if (!$project || !$project->onedrive_folder_id) {
            throw new \RuntimeException('OneDrive folder has not been created for this project.');
        }

        if ($folder->onedrive_folder_id && $this->od()->itemExists($folder->onedrive_folder_id)) {
            return $folder->onedrive_folder_id;
        }

        $id = $this->od()->findOrCreateSubFolderById($project->onedrive_folder_id, $folder->display_name);

        // Bukan aktivitas user: jangan geser Last Update project.
        $folder->onedrive_folder_id = $id;
        $folder->saveQuietly();

        return $id;
    }

    /**
     * Buat semua subfolder (standar + tambahan) di folder OneDrive project.
     * Kegagalan per folder dicatat saja: folder itu akan dibuat saat upload.
     */
    public function syncAll(DeliveryProject $project): void
    {
        if (!$project->onedrive_folder_id) {
            return;
        }

        DeliveryProjectDocumentFolder::ensureDefaults($project);

        $folders = DeliveryProjectDocumentFolder::where('delivery_projects_id', $project->id)->ordered()->get();
        foreach ($folders as $folder) {
            try {
                $this->ensureOneDriveFolder($folder, $project);
            } catch (\Throwable $e) {
                Log::warning('OneDrive document sub-folder sync failed', [
                    'project_id' => $project->id,
                    'folder'     => $folder->display_name,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->planCostFolderId($project);
        } catch (\Throwable $e) {
            Log::warning('OneDrive Plan Cost folder sync failed', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Folder "Plan Cost" di dalam "01. Discover". Project lama yang masih punya
     * "Plan Cost" langsung di folder project: folder itu dipindah ke Discover
     * (item ID file tetap, jadi share link supporting document tidak putus).
     */
    public function planCostFolderId(DeliveryProject $project): string
    {
        $discover   = DeliveryProjectDocumentFolder::defaultFolder($project, DeliveryProjectDocumentFolder::DISCOVER_NUMBER);
        $discoverId = $this->ensureOneDriveFolder($discover, $project);

        $inDiscover = $this->findChildFolder($discoverId, 'Plan Cost');
        if ($inDiscover) {
            return $inDiscover;
        }

        $legacy = $this->findChildFolder($project->onedrive_folder_id, 'Plan Cost');
        if ($legacy) {
            $this->od()->moveItem($legacy, $discoverId);
            Log::info('Plan Cost folder moved into Discover', ['project_id' => $project->id]);
            return $legacy;
        }

        return $this->od()->createSubFolder($discoverId, 'Plan Cost');
    }

    /**
     * Rename subfolder OneDrive mengikuti nama tampilan baru (bila sudah dibuat).
     */
    public function renameOneDriveFolder(DeliveryProjectDocumentFolder $folder): void
    {
        if ($folder->onedrive_folder_id && $this->od()->itemExists($folder->onedrive_folder_id)) {
            $this->od()->renameItem($folder->onedrive_folder_id, OneDriveService::sanitizeSegment($folder->display_name));
        }
    }

    /**
     * Hapus subfolder OneDrive hanya bila benar-benar kosong. File yang ditaruh
     * langsung lewat OneDrive (di luar EcoSystem) tidak ikut terhapus.
     *
     * @return bool true bila folder OneDrive terhapus atau memang tidak ada.
     */
    public function deleteOneDriveFolderIfEmpty(DeliveryProjectDocumentFolder $folder): bool
    {
        if (!$folder->onedrive_folder_id) {
            return true;
        }

        try {
            $item = $this->od()->getItem($folder->onedrive_folder_id, 'id,folder');
        } catch (\Throwable $e) {
            // Sudah tidak ada di OneDrive.
            return true;
        }

        if ((int) ($item['folder']['childCount'] ?? 0) > 0) {
            return false;
        }

        $this->od()->deleteItem($folder->onedrive_folder_id);
        return true;
    }

    /** Pindahkan file dokumen ke subfolder OneDrive folder tujuan. */
    public function moveFile(string $itemId, DeliveryProjectDocumentFolder $target, DeliveryProject $project): void
    {
        $this->od()->moveItem($itemId, $this->ensureOneDriveFolder($target, $project));
    }

    private function findChildFolder(string $parentId, string $name): ?string
    {
        $needle = mb_strtolower($name);
        foreach ($this->od()->listSubFoldersByParentId($parentId) as $child) {
            if (mb_strtolower($child['name']) === $needle) {
                return $child['id'];
            }
        }
        return null;
    }
}
