---
key: ms-business-partner-record
chapter: master
order: 21
title: Business partner record: Basic Data, Address, Contact Person, Identification, Bank, Credential, Attachment, Report Templates
summary: Read and update one business partner, tab by tab, and give customer contacts access to the customer portal.
requires: [customer.section.basic_data.view, customer.section.address.view, customer.section.contact.view, customer.section.contact.group, customer.section.identification.view, customer.section.bank.view, customer.section.credential.view, customer.section.history.view, customer.section.attachment.view, customer.section.report_templates.view]
updated: 2026-11-02
---
Click a business partner in `Master` › `Business Partner` to open its record. The header shows the name, status, code, type, email, phone, group, category and industry. The tabs hold the details.

## Who uses this

Sales, HR, project and support staff, and administrators. Every tab has its own `view` and `update` permission in Menu Access, so you see only the tabs your role was given and `Save` is hidden where you may not change data.

> [!IMPORTANT]
> `Bank Account` and `Credential` hold sensitive data. Credentials are secrets for the customer's systems: open them only when your work needs it, and never copy them into chat or tickets.

## Before you start

- Contacts are people at the customer. Only a contact who was granted access can sign in to the customer portal (JARVIES).
- A contact can see more or fewer tickets depending on `Ticket Access` and groups, described below.

## Step by step

![The Business Partner Detail page with the header card and the Basic Data tab](images/guide/mst-partner-detail.png)

*Screenshot with sample data.*

1. Open `Master` › `Business Partner` and click a row. Use `Back to List` to return.
2. `Basic Data`: code, type, company email, email domain, names and search terms, and the customer information: group, parent customer, credit limit type, industry sector and the account executives. The `Audit Information` shows who created and last changed it. Choose `Save Changes` after editing.
3. `Address`: one or more addresses of the company.
4. `Identification`: company documents with their number, issuer and validity.
5. `Bank Account`: the bank accounts of the company.
6. `Credential`: the credential notes kept for this customer. Choose `Save` or press Ctrl+S after editing.
7. `Attachment`: supporting files.
8. `Report Templates`: Word templates (`.docx`) used by `Word Report Generator`. Choose `Upload Template` once and reuse it for every report of this customer.

### Contact Person

![The Contact Person tab with the contact table, Jarvies access and ticket access columns](images/guide/mst-partner-contact.png)

*Screenshot with sample data.*

1. Choose the `Contact Person` tab. Each contact shows name, position, department, phone, email, validity, `Jarvies Access`, `Ticket Access` and `Group`.
2. Choose `New` to add a contact, fill the general data and communication details and `Save`. `Copy` duplicates a contact and `Delete` removes it.
3. To let a contact sign in, select the contact and choose `Grant Jarvies Access`. Type the login email and confirm: a password setup email is sent automatically. `Revoke Jarvies Access` takes the access back.
4. `Ticket Access` marks a contact as an administrator who sees every ticket of the company. Other contacts see their own tickets, and the tickets of the delivery supports where they are Customer PIC.
5. Choose `Groups` to create contact groups. Members of the same group can see each other's tickets in JARVIES. Administrator contacts cannot be added, they already see everything.

## What happens next

Changes apply at once and appear in `Control Center` › `Audit Log`. A contact with new access receives the password setup email and can sign in to JARVIES.

## Common questions

**A customer cannot see a ticket.** Check the contact's `Ticket Access`, the group and, for a support, the Customer PIC list in `Delivery` › `Support`.

**The password email did not arrive.** Check the login email for typos and the spam folder, then revoke and grant access again.

<!-- sources: routes/web.php, resources/views/master/customer/show.blade.php, resources/views/master/customer/sections/contact.blade.php -->
