<?php

namespace Tests\Unit;

use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Tests\TestCase;

/**
 * public_url menentukan tautan View/Download di halaman ticket. Lampiran email
 * tanpa ID Graph (balasan customer dari portal JARVIES saat lookup Sent Items
 * gagal, tiket #26110379) dulu mendapat URL null -> href="null" -> 404.
 */
class TicketAttachmentPublicUrlTest extends TestCase
{
    private function attachment(array $attrs, ?string $emailMessageId): TicketAttachment
    {
        $att = new TicketAttachment();
        $att->forceFill(array_merge([
            'id'              => 3647,
            'message_id'      => 23527,
            'attachment_type' => 'image',
            'file_name'       => 'Jumlah Mandays CR.jpeg',
        ], $attrs));

        $msg = new TicketMessage();
        $msg->forceFill(['id' => 23527, 'email_message_id' => $emailMessageId]);
        $att->setRelation('message', $msg);

        return $att;
    }

    public function test_email_attachment_without_graph_ids_goes_through_the_recovering_proxy(): void
    {
        $att = $this->attachment([], '<abc@PROD.OUTLOOK.COM>');

        $this->assertSame(route('attachments.show', 3647), $att->public_url);
    }

    public function test_attachment_without_email_message_id_keeps_old_fallback(): void
    {
        $att = $this->attachment([], null);

        $this->assertNull($att->public_url);
    }

    public function test_attachment_without_file_name_cannot_be_matched_and_keeps_old_fallback(): void
    {
        $att = $this->attachment(['file_name' => null], '<abc@PROD.OUTLOOK.COM>');

        $this->assertNull($att->public_url);
    }

    public function test_existing_link_url_still_wins_for_non_proxyable_rows(): void
    {
        $att = $this->attachment(['link_url' => 'https://example.com/file.pdf'], '<abc@PROD.OUTLOOK.COM>');

        $this->assertSame('https://example.com/file.pdf', $att->public_url);
    }

    public function test_cloud_link_rows_are_not_treated_as_recoverable_email_attachments(): void
    {
        $att = $this->attachment(['attachment_type' => 'link', 'link_url' => null], '<abc@PROD.OUTLOOK.COM>');

        $this->assertNull($att->public_url);
    }

    public function test_rows_with_graph_ids_or_local_file_are_unchanged(): void
    {
        $withIds = $this->attachment(['graph_message_id' => 'm', 'graph_attachment_id' => 'a'], null);
        $local   = $this->attachment(['file_path' => 'ticket-attachments/x.jpeg'], null);

        $this->assertSame(route('attachments.show', 3647), $withIds->public_url);
        $this->assertSame(route('attachments.show', 3647), $local->public_url);
    }
}
