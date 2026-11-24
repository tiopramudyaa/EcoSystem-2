<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Modal Propose Customer Mandays (ticket/show.blade.php): tombol hapus baris/kolom tidak boleh
 * menyelipkan nama activity/modul ke dalam string onclick, dan tombol Delete Draft harus
 * dievaluasi ulang setelah draft tersimpan.
 */
class PicMandaysModalTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(resource_path('views/ticket/show.blade.php'));
    }

    public function test_remove_buttons_read_the_name_from_a_data_attribute(): void
    {
        $src = $this->source();

        $this->assertStringContainsString('picRemoveActivityRow(this.dataset.act)', $src);
        $this->assertStringContainsString('picRemoveModuleCol(this.dataset.mod)', $src);
        $this->assertStringNotContainsString("picRemoveActivityRow('\${actEsc}')", $src);
        $this->assertStringNotContainsString("picRemoveModuleCol('\${mEsc}')", $src);
    }

    public function test_delete_draft_uses_post_not_the_delete_method(): void
    {
        $src = $this->source();
        $start = strpos($src, 'async function picDeleteDraft()');
        $end = strpos($src, 'function picMandaysUpdateSidebarBadge');
        $body = substr($src, $start, $end - $start);

        $this->assertStringContainsString("pic-draft/delete", $body);
        $this->assertStringNotContainsString("'DELETE'", $body);
        $this->assertStringContainsString(
            "Route::post('/{ticketId}/mandays/pic-draft/delete'",
            file_get_contents(base_path('routes/api.php'))
        );
    }

    public function test_delete_draft_button_is_updated_after_saving(): void
    {
        $src = $this->source();
        $start = strpos($src, 'async function picSaveDraft()');
        $end = strpos($src, 'function picStartNewVersion()');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertStringContainsString("picBtnDeleteDraft", substr($src, $start, $end - $start));
    }
}
