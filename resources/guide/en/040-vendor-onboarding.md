---
key: vendor-onboarding
chapter: business-legal
order: 40
title: Vendor Onboarding
summary: Register a vendor, check its documents and declarations, and turn a verified vendor into a Business Partner.
requires: [legal.vendor-onboarding, legal.vendor-onboarding.approve, legal.vendor-onboarding.settings, "@approver"]
updated: 2026-11-03
---
Vendor Onboarding is the review that comes before a company or an individual becomes a vendor. You register the vendor, attach its identity and tax documents, record its due diligence answers and send it to review. Once it is `Verified`, one click creates the Business Partner of type Vendor.

## Who uses this

- **Legal and procurement staff** register vendors and start the review.
- **Reviewers** are the positions or people named on an approval step. They decide on the registrations that reach them and find them in [[my-approvals]].
- **Administrators** set the approval steps, the document types and the questions in `Settings`.

## Before you start

- Collect the vendor's documents, for example NIB, NPWP and deed of incorporation for a company, or KTP, NPWP and CV for an individual.
- To create a Business Partner you also need the `Create` permission on `Master` › `Business Partner`.

## Step by step

### Register a vendor

1. Open `Business & Legal` › `Vendor Onboarding` and click `Add Company` or `Add Individual`.
2. Fill in the profile and bank account. For a company, add the person in charge (PIC).
3. In `Documents`, pick the files for each document type. Required types are marked with a red star. Several files can be added to one type.
4. Answer the due diligence declarations and click `Save`.
5. Click `Submit registration`. The status becomes `Submitted`.

> [!IMPORTANT]
> Documents are private. Only people who can open the registration can open them, and they have no public link.

### Send it to review

1. Open the registration and click `Start review`. The data can no longer be edited.
2. The first reviewer is notified. Each reviewer reads the profile, documents and declarations, writes a note and clicks `Approve` or `Reject`.
3. After the last step approves, the status is `Verified`. A rejection ends the review. To fix it, open the registration and click `Reopen to correct`.

### Create the Business Partner

1. Open a `Verified` registration.
2. Click `Create Business Partner`. The vendor is added to `Master` › `Business Partner` with its NPWP, NIB or NIK, bank account, address and contact.
3. If the vendor already exists as a Business Partner, choose it under `Link` instead.

> [!NOTE]
> If an email, tax number or bank account already belongs to another Business Partner, it is not copied and the message tells you which one was left out. Documents stay in Vendor Onboarding.

### Change the settings

1. Open the `Settings` tab.
2. Add or change approval steps, document types and questions. Switching a document type or question off keeps what was already uploaded or answered.

## What happens next

A linked Business Partner can be chosen as the vendor in Delivery Support.

## Related topics

- [[business-partners]]
- [[sales-compliance]]
- [[my-approvals]]

<!-- sources: routes/legal-compliance.php, app/Http/Controllers/Legal/VendorOnboardingController.php, app/Http/Controllers/Legal/WorkflowSettingsController.php, app/Services/Legal/VendorOnboardingService.php, app/Services/Legal/StepWorkflowService.php, resources/views/legal/vendor-onboarding/index.blade.php, resources/views/legal/vendor-onboarding/form.blade.php, resources/views/legal/vendor-onboarding/show.blade.php, resources/views/legal/workflow-settings.blade.php -->
