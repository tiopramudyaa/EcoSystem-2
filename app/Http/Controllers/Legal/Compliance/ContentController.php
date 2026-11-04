<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Services\Compliance\ComplianceCatalog;
use Illuminate\Http\Request;

/**
 * Legal → Compliance Management → Content tab (URL /legal/compliance/content): what My Compliance shows staff — knowledge-base articles,
 * Do & Don't rules, critical-rule cards and training sessions. Lists, fields and the add / edit / delete come from CatalogController.
 * Article HTML is sanitised before it is stored.
 */
class ContentController extends CatalogController
{
    protected function tab(): string
    {
        return 'content';
    }

    protected function keys(): array
    {
        return array_values(array_diff(ComplianceCatalog::SECTIONS, ComplianceCatalog::SETTINGS));
    }

    public function index(Request $request)
    {
        return $this->show($this->currentKey($request));
    }

    protected function prepare(string $section, array &$data, ?int $id): ?string
    {
        if ($section === 'articles') {
            $data['updated_by'] = (int) session('user.id');
        }

        return null;
    }
}
