<?php

namespace App\Services\Approval;

use Illuminate\Database\Eloquent\Model;

/**
 * Guard for approve / reject inside DB::transaction().
 *
 * The services check "may this person act?" (canAct) BEFORE they open the transaction, so two
 * simultaneous clicks, or two approvers deciding the same step, would both pass that check and
 * both write. Call this as the first statement INSIDE the transaction: it takes a row lock on the
 * document and on the step, then re-confirms the step is still waiting. False means somebody else
 * decided first, so the caller must write nothing and report "already decided".
 */
trait LocksApprovalStep
{
    protected function lockStepStillWaiting(Model $document, Model $approval): bool
    {
        $freshDoc = $document->newQuery()->whereKey($document->getKey())->lockForUpdate()->first();
        if (!$freshDoc) {
            return false;
        }
        if (method_exists($freshDoc, 'isOpen') && !$freshDoc->isOpen()) {
            return false;
        }

        $freshStep = $approval->newQuery()->whereKey($approval->getKey())->lockForUpdate()->first();

        return $freshStep !== null && $freshStep->status === 'waiting';
    }
}
