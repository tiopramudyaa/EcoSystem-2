<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approval bertingkat Customer Mandays (2 level internal sebelum dikirim ke customer).
 *
 * Desain "konfigurasi ≠ riwayat":
 *  - customer_mandays_approval_steps  = KONFIGURASI (2 baris tetap: Level 1 & Level 2),
 *    diatur admin di Management > Flow > Customer Mandays Approval.
 *  - customer_mandays_approvals       = SNAPSHOT per versi proposal, disalin saat PIC
 *    submit. Mengubah konfigurasi tidak pernah mengubah proposal yang sedang berjalan.
 *  - customer_mandays.current_step_order = level yang sedang menunggu (NULL = tidak
 *    sedang dalam approval internal).
 *
 * Status baru:
 *  - pending_approval : menunggu Level 1 / Level 2.
 *  - rejected         : ditolak di salah satu level; PIC merevisi sebagai versi baru.
 * `pending_helpdesk` tetap dipakai dengan arti "lolos 2 level, siap dikirim ke customer".
 *
 * Proposal yang sudah `pending_helpdesk` saat migrasi berjalan dibiarkan apa adanya
 * (dianggap sudah lolos) — tidak ada snapshot yang dibuat untuknya.
 */
return new class extends Migration
{
    private const CM_STATUSES_NEW = ['draft', 'pending_approval', 'pending_helpdesk', 'sent_to_chat', 'approved', 'rejected', 'canceled'];
    private const CM_STATUSES_OLD = ['draft', 'pending_helpdesk', 'sent_to_chat', 'approved', 'canceled'];
    private const TK_STATUSES_NEW = ['none', 'pic_draft', 'pending_approval', 'pending_helpdesk', 'sent_to_chat', 'approved', 'rejected', 'canceled'];
    private const TK_STATUSES_OLD = ['none', 'pic_draft', 'pending_helpdesk', 'sent_to_chat', 'approved', 'canceled'];

    public function up(): void
    {
        if (!Schema::hasTable('customer_mandays_approval_steps')) {
            Schema::create('customer_mandays_approval_steps', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('order_seq');
                $table->string('name', 100);
                $table->string('approver_type', 20)->default('role'); // role | employee
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->json('approver_employee_ids')->nullable();
                $table->timestamps();

                $table->unique('order_seq', 'cm_steps_order_unique');
                $table->foreign('approver_role_id', 'cm_steps_role_fk')
                    ->references('id')->on('employee_role')->nullOnDelete();
            });

            $roleId = fn (string $name) => DB::table('employee_role')->where('name', $name)->value('id');
            $now    = now();

            DB::table('customer_mandays_approval_steps')->insert([
                [
                    'order_seq'             => 1,
                    'name'                  => 'Admin Approval',
                    'approver_type'         => 'role',
                    'approver_role_id'      => $roleId('EC Administrator') ?? MenuRegistrar::adminRoleId(),
                    'approver_employee_ids' => null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ],
                [
                    'order_seq'             => 2,
                    'name'                  => 'Helpdesk Review',
                    'approver_type'         => 'role',
                    'approver_role_id'      => $roleId('Delivery Support Service Helpdesk'),
                    'approver_employee_ids' => null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ],
            ]);
        }

        if (!Schema::hasTable('customer_mandays_approvals')) {
            Schema::create('customer_mandays_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_mandays_id');
                $table->unsignedTinyInteger('order_seq');

                // Salinan definisi langkah saat proposal disubmit (bukan FK, R8).
                $table->string('step_name', 100);
                $table->string('approver_type', 20);
                $table->unsignedBigInteger('approver_role_id')->nullable();
                $table->json('approver_employee_ids')->nullable();

                // Hasil tindakan.
                $table->string('status', 20)->default('waiting'); // waiting|approved|rejected|skipped
                $table->unsignedBigInteger('acted_by')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('customer_mandays_id', 'cm_appr_cm_fk')
                    ->references('id')->on('customer_mandays')->onDelete('cascade');
                $table->foreign('acted_by', 'cm_appr_actor_fk')
                    ->references('employee_id')->on('employee')->nullOnDelete();
                $table->unique(['customer_mandays_id', 'order_seq'], 'cm_appr_cm_order_unique');
                $table->index('status', 'cm_appr_status_idx');
            });
        }

        if (!Schema::hasColumn('customer_mandays', 'current_step_order')) {
            Schema::table('customer_mandays', function (Blueprint $table) {
                $table->unsignedTinyInteger('current_step_order')->nullable()->after('status');
            });
        }

        $this->setEnums(self::CM_STATUSES_NEW, self::TK_STATUSES_NEW);
    }

    public function down(): void
    {
        // Kembalikan status baru ke nilai lama yang paling dekat sebelum ENUM dipersempit.
        DB::table('customer_mandays')->whereIn('status', ['pending_approval', 'rejected'])->update(['status' => 'draft']);
        DB::table('ticket')->whereIn('mandays_proposal_status', ['pending_approval', 'rejected'])->update(['mandays_proposal_status' => 'pic_draft']);
        $this->setEnums(self::CM_STATUSES_OLD, self::TK_STATUSES_OLD);

        if (Schema::hasColumn('customer_mandays', 'current_step_order')) {
            Schema::table('customer_mandays', function (Blueprint $table) {
                $table->dropColumn('current_step_order');
            });
        }

        Schema::dropIfExists('customer_mandays_approvals');
        Schema::dropIfExists('customer_mandays_approval_steps');
    }

    /** ENUM hanya ada di MySQL/MariaDB; SQLite (tes) menyimpan string biasa. */
    private function setEnums(array $cmStatuses, array $ticketStatuses): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $list = fn (array $v) => "'" . implode("','", $v) . "'";

        DB::statement('ALTER TABLE customer_mandays MODIFY COLUMN status ENUM(' . $list($cmStatuses) . ") NOT NULL DEFAULT 'draft'");
        DB::statement('ALTER TABLE ticket MODIFY COLUMN mandays_proposal_status ENUM(' . $list($ticketStatuses) . ") NOT NULL DEFAULT 'none'");
    }
};
