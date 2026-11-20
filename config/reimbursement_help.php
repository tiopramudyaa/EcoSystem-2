<?php

/*
|--------------------------------------------------------------------------
| Bantuan (?) untuk Reimbursement Settings
|--------------------------------------------------------------------------
| Isi popover bantuan. Satu entri per bagian layar, dikunci dengan `key` yang dipakai
| di @include('hr-general.reimbursement.components.help-tip', ['key' => ...]).
|
| mode:
|   'guided'  : penjelasan + arahan (nilai yang disarankan, akibat bila salah, tombol "Use recommended")
|   'explain' : penjelasan saja
|
| Bidang entri:
|   title        judul popover (sama dengan judul bagian di layar)
|   what         satu atau dua kalimat: fungsi bagian ini
|   options      daftar [label, penjelasan] untuk tiap pilihan yang tampil di layar
|   recommended  ['text' => alasan, 'values' => [nama input => nilai]]. `values` hanya mengisi
|                input di layar; tidak pernah menyimpan. Nama input harus ada di form (diuji).
|   risk         akibat bila salah memilih
|   topic        kunci topik panduan (bawaan: `topic` di bawah)
|
| Teks harus cocok dengan perilaku kode. Jangan memakai tanda pisah panjang.
*/

use App\Models\ObjectFund\ObjectFlowSetting;
use App\Services\ObjectFund\ObjectAccessPolicy;

return [
    'mode'  => 'guided',
    'topic' => 'reimbursement-management',

    'items' => [

        // ── Reimbursement Rules ───────────────────────────────────────────
        'rules.main' => [
            'title' => 'Reimbursement Rules',
            'what'  => 'Limits and required fields checked when an employee submits a reimbursement. They apply to documents submitted from now on; documents already submitted keep the rules they were submitted under.',
            'options' => [
                ['0', 'Means no limit.'],
                ['Flag', 'The document is accepted and highlighted for the approver.'],
                ['Block', 'The document is refused and the employee sees the reason.'],
            ],
            'risk' => 'A rule set to block does nothing while its limit is 0. The page warns you when that happens.',
        ],

        'rules.printed' => [
            'title' => 'Printed Document',
            'what'  => 'The company name and letterhead that appear on the printed reimbursement form and the Excel export.',
            'risk'  => 'Changing them affects every printout from now on, including documents printed again later.',
        ],

        'rules.dates' => [
            'title' => 'Dates & Deadlines',
            'what'  => 'Which request dates and receipt dates are accepted, and whether a closed reporting period can still receive documents.',
            'options' => [
                ['Allow a future request date', 'Off by default because a reimbursement is money already spent.'],
                ['Request window (days)', 'How far back the request date may be, counted from today. Blocks when exceeded.'],
                ['Closed reporting period', 'Ignore allows everything. Block employees only still lets users with Edit or Delete access act. Block everyone has no exception.'],
                ['Maximum receipt age (days)', 'How old a receipt may be, counted back from the request date. A receipt can never be dated after the request.'],
            ],
            'recommended' => [
                'text'   => 'Keep future dates off. Set the day limits to match how fast your employees normally submit.',
                'values' => ['allow_future_date' => false],
            ],
            'risk' => 'A very short request window blocks honest late submissions. Zero removes the limit.',
        ],

        'rules.amounts' => [
            'title' => 'Items & Amounts',
            'what'  => 'Size limits for one document: number of items, smallest item, and largest total.',
            'options' => [
                ['Maximum items per document', 'Blocks when exceeded.'],
                ['Minimum amount per item', 'Smaller items are flagged, never blocked.'],
                ['Maximum total per document', 'Applies to documents in IDR. Choose below whether exceeding it is flagged or blocked.'],
            ],
            'risk' => 'Blocking a total only works while the maximum is above 0.',
        ],

        'rules.evidence' => [
            'title' => 'Evidence & Completeness',
            'what'  => 'What proof an item must carry: a supporting document link, a receipt number, a minimum title length, and which link hosts are accepted.',
            'options' => [
                ['Require a supporting document link', 'Off means a missing link is only flagged.'],
                ['Require a receipt number', 'Off means items without one are flagged.'],
                ['Allowed link hosts', 'Comma separated; a full address is trimmed to its host. Empty accepts any link, but only while a link is not required.'],
            ],
            'risk' => 'Requiring everything makes submission slower. Start by flagging, then require what approvers keep asking for.',
        ],

        'rules.approval' => [
            'title' => 'Approval Rules',
            'what'  => 'Whether a requester may approve their own document, whether approvers may change amounts, and who is suggested when self-approval is blocked.',
            'options' => [
                ['Allow self-approval', 'A requester who holds an approver position may approve their own document. It is always flagged.'],
                ['Approvers may adjust amounts', 'Only while the document waits for their own step. Every change is flagged and logged.'],
                ['Fallback approver position', 'Suggested to the person who is blocked when self-approval is off.'],
            ],
            'recommended' => [
                'text'   => 'Keep self-approval off so every document is seen by a second person.',
                'values' => ['allow_self_approval' => false],
            ],
            'risk' => 'With self-approval on and only one active step, a requester can take their own document to approved unseen.',
        ],

        // ── Objects and Funds ─────────────────────────────────────────────
        'object.main' => [
            'title' => 'Objects and Funds',
            'what'  => 'Each reimbursement or cash advance item is charged to an Object: an Office, a Project, or a Support ticket. The Object decides who approves and which fund is checked.',
            'options' => [
                ['Use Objects and funds for new reimbursements', 'Turns the concept on for new reimbursements. Documents already submitted keep their flow.'],
                ['Use Objects and funds for new cash advances', 'A separate switch for cash advances and their reports, so each module can start when you are ready.'],
            ],
            'recommended' => [
                'text' => 'Turn the switches on only after the Setup checklist is complete: responsible persons for offices, fallback approvers, and allocations where you want a fund checked.',
            ],
            'risk' => 'Turning a switch off returns that module to the previous behaviour. No data is deleted.',
        ],

        'object.checklist' => [
            'title' => 'Setup checklist',
            'what'  => 'Shows what is still missing before the Object concept works well, such as an office without a responsible person.',
        ],

        'object.project_approvals' => [
            'title' => 'Project approvals',
            'what'  => 'Who approves items charged to a project. They are assigned automatically at submission and do not change afterwards.',
            'options' => [
                ['Project Admin approves / Project Manager approves', 'Each can be turned on or off. At least one must stay on while Objects are in use.'],
                ['Order', 'Which of the two is asked first.'],
                ['Fallback role and person', 'Used when a project has no admin or manager.'],
            ],
            'recommended' => [
                'text'   => 'Admin first, then Manager: the admin checks the paperwork, the manager approves the spending. Always set a fallback.',
                'values' => [
                    'project_admin_enabled'   => true,
                    'project_manager_enabled' => true,
                    'project_order'           => ObjectFlowSetting::ORDER_ADMIN_FIRST,
                ],
            ],
            'risk' => 'A project with no approver and no fallback cannot receive a submission; the employee is told why.',
        ],

        'object.support_approvals' => [
            'title' => 'Support approvals',
            'what'  => 'Who approves items charged to a support ticket.',
            'options' => [
                ['Ticket Lead approves', 'The lead set on the ticket.'],
                ['Support Manager approves', 'An additional manager step, if your structure needs one.'],
                ['Fallback', 'Used when a ticket has no one to approve.'],
            ],
            'recommended' => [
                'text'   => 'Keep the Ticket Lead on. Add the Support Manager only if tickets need a second review.',
                'values' => ['support_lead_enabled' => true],
            ],
        ],

        'object.access' => [
            'title' => 'Who can charge costs to a project or ticket',
            'what'  => 'Decides who may choose a project or ticket on a reimbursement or cash advance. The check is on the document owner, also when HR creates the document for them. Office is not restricted.',
            'options' => [
                [ObjectAccessPolicy::MODE_LABELS[ObjectAccessPolicy::TEAM], 'The lead and members of that project or ticket, as set in the Project and Ticket menus.'],
                [ObjectAccessPolicy::MODE_LABELS[ObjectAccessPolicy::LEAD], 'Only the lead (project manager, delivery manager and the like, or the ticket lead).'],
                [ObjectAccessPolicy::MODE_LABELS[ObjectAccessPolicy::EVERYONE], 'Any employee.'],
            ],
            'recommended' => [
                'text'   => 'Team for both. People who work on a project or ticket can charge to it, and people who do not cannot. Use an exception for the rare ticket that everyone may use.',
                'values' => [
                    'access_project' => ObjectAccessPolicy::TEAM,
                    'access_support' => ObjectAccessPolicy::TEAM,
                ],
            ],
            'risk' => 'Lead only means members cannot charge until the lead acts for them. Everyone allows charging to a project the employee does not work on.',
        ],

        'object.policy' => [
            'title' => 'When an amount exceeds the remaining fund',
            'what'  => 'What happens when a document is larger than what is left in the fund, set separately for Project, Office and Support.',
            'options' => [
                ['Warn', 'The document is accepted if the requester writes a reason (at least 10 characters). Approvers see the reason and the document is marked Over fund allocation.'],
                ['Block', 'The document is refused.'],
            ],
            'recommended' => [
                'text'   => 'Start with Warn everywhere. Move an Object type to Block once its fund is a hard limit.',
                'values' => [
                    'policy_project' => ObjectFlowSetting::POLICY_WARN,
                    'policy_office'  => ObjectFlowSetting::POLICY_WARN,
                    'policy_support' => ObjectFlowSetting::POLICY_WARN,
                ],
            ],
            'risk' => 'An Object without a fund is never checked, whichever you choose. Under Block, the last approval is refused when the fund is short.',
        ],

        'object.offices' => [
            'title' => 'Office funds and responsible person',
            'what'  => 'Each office needs a responsible person who approves its items. A monthly fund is optional.',
            'options' => [
                ['Responsible person', 'Choose the role, then the person.'],
                ['Fund active', 'Only offices with this on have a fund that is checked. The monthly amount follows the date of the document.'],
            ],
            'risk' => 'An office without a responsible person cannot receive a submission.',
        ],

        'object.allocations' => [
            'title' => 'Project and ticket fund allocations',
            'what'  => 'The amount that may be reimbursed for one project or ticket, optionally per category such as Transport. The fund decreases only when a document is fully approved.',
            'options' => [
                ['Typed amount', 'You enter the amount.'],
                ['Project plan (read only)', 'Projects only. The amount follows the released amount of a cost line in the project Plan Cost (or its budget when nothing is released) and updates by itself. The plan is never changed.'],
                ['Category', 'Empty means one general allocation for every category without its own.'],
            ],
            'recommended' => [
                'text' => 'Use Project plan when the Plan Cost is maintained, so the fund and the plan do not drift apart. Tickets have no plan budget, so type their amount.',
            ],
            'risk' => 'An allocation cannot be set lower than what approved documents already used. A project or ticket without an allocation is not checked.',
        ],

        'object.exceptions' => [
            'title' => 'Exceptions for one project or ticket',
            'what'  => 'A different "who can charge" rule for a single project or ticket. It replaces the default above for that one only.',
            'recommended' => [
                'text' => 'Use sparingly, for example one ticket that every employee may charge to. Remove an exception when it is no longer needed.',
            ],
            'risk' => 'Removing an exception returns the project or ticket to the default rule immediately.',
        ],

        // ── Approval Workflow ─────────────────────────────────────────────
        'workflow.reimbursement' => [
            'title' => 'Approval Workflow',
            'what'  => 'Steps that apply to every reimbursement, after the steps assigned automatically from the Object. Use them for Accounting, Cashier and the final approver.',
            'options' => [
                ['Signs as', 'Accounting or Cashier fills that column on the printed document. Without such a step the column stays empty.'],
                ['Mandatory', 'Checked: the person here always acts. Unchecked: the requester chooses among the people listed.'],
            ],
            'recommended' => [
                'text' => 'Add one Accounting step and one Cashier step, then the final approver.',
            ],
            'risk' => 'Do not add project, ticket or office approvers here. They are assigned from Objects and Funds, and the same person would be asked twice.',
        ],
    ],
];
