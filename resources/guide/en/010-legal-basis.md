---
key: legal-basis
chapter: business-legal
order: 10
title: Legal Basis
summary: Keep the register of laws and regulations, and the figures that offboarding, payroll and overtime can read from it.
requires: [legal, finance.legal-basis, finance.legal-basis.settings]
updated: 2026-11-05
---
Legal Basis is the register of the laws and regulations behind the calculation rules in EcoSystem: PPh 21, BPJS, Overtime, Contract (PKWT) and Offboarding. Each rule also holds the figures it uses, so some modules can read their rates from here.

## Who uses this

Legal, HR and Finance reviewers, and administrators. The page has two tabs: `Register` and `Settings`. You only see the tabs your role allows.

## Before you start

- Decide who reviews the rules and who owns each one.
- The first entries in the register were written from secondary sources. A person must check them against the official text before relying on them, above all the TER tables, PTKP and the BPJS wage ceilings.

## Step by step

### Read the register

1. Open `Business & Legal` › `Legal Basis` › `Register`.
2. Filter by module if you want only one area, for example BPJS.
3. Read the columns `Regulation`, `In force since`, `Amended by`, `Status` and `Your confidence`. Use the source link to open the official text.

One row is one version of a rule. The status is `Needs review` (yellow), `Reviewed` (green, with who reviewed it and when) or `Superseded` (an old version that is no longer used).

### Add a rule or a new version

1. Click `Add rule or new version`.
2. Fill in the regulation details, `In force since`, `Your confidence` and the `Owner (responsible person)`, chosen from active employees. Add the source link and notes if you have them.
3. Fill in the `Calculation values`. These are labelled fields, and tables with `+ Add a row` and a ✕ button. Type rupiah as 60.000.000 or 60000000, and percentages as percentages (5 means 5%). A comma or a dot both work for decimals. Leave a "No limit" field empty. Letters are refused with "Use numbers only".
4. Save. The entry is added as `Needs review`.

A new effective date creates a new version. The old version stays in the history, and the system uses the version in force on the calculation date. Use `Supersede` to retire a version and `Restore` to bring it back.

> [!IMPORTANT]
> Changing the content of an entry sets it back to `Needs review`. Changing only the `Owner` does not.

### Review a change

1. On a version that has an older one, click `Changes`.
2. Compare `Previous version` (red) with `This version` (green): the regulation details first, then the calculation values.
3. Check them against the official text, then click `Mark reviewed`. Click `Needs review` to send an entry back.

### Owners and review reminders

A review is valid for 12 months. The summary card `Review overdue (12+ months)` counts late rules, with a note on those due within 30 days. A row shows `Review due in N days` (yellow) or `Review overdue by N days` (red). On working days at 08:15 the owner gets a notification when a review is due within 30 days or late, or when a rule has waited in `Needs review` for over 30 days. A rule gets at most one reminder every 7 days. A rule with no owner gets no reminder, but the page still flags it.

### Choose who reads the figures (Settings)

On the `Settings` tab, a readiness list shows rules that still have no values.

- **Offboarding.** The Final Settlement reads four rules: the multiplier by exit reason, the severance and UPMK table, severance tax and PKWT compensation. The leave payout divisor comes from `Offboarding Settings`.
- **Payroll.** On the card `Where Payroll reads its rates`, choose `Payroll Settings` (default) or `Legal Basis` for the PPh 21 rates (progressive rates, occupational cost, PTKP, TER) and the BPJS rates and wage ceilings. Company choices stay in `Payroll Settings`: active year, JKK class, the BPJS and PPh 21 switches, employer contributions, schedule and rounding.
- **Overtime.** On the card `Overtime multipliers`, choose `Off — built-in standard` (default: PP 35/2021, divisor 173) or `On — read from Legal Basis`. Companies that do not use overtime can leave it Off. Overtime is paid only when it is switched on in Payroll `Settings`.

Click `Save source` or `Save overtime source`. If the two sources give different figures, a list of differences appears and you tick a confirmation, because payroll results change. Going back to the old source is always allowed. If a rule is incomplete, the switch is refused with an explanation. While the source is `Legal Basis`, the PPh 21 and BPJS settings pages show a banner saying the figures are managed here, and changes to PTKP, progressive rates or TER there are refused.

Yellow in the `Calculation values` box means a calculator reads it. Blue means a reference copy only. Other modules, such as Contract, use the register as a reference.

The table `Where the legal basis applies` sets, per module, `Show panel`, `Warn` and a mode: `Draft mode` or `Enforced`. Click `Save switches`.

> [!WARNING]
> `Enforced` can stop a module until its rules are `Reviewed`. Check the pending column first.

## What happens next

Where enabled, Payroll and other pages show a legal-basis panel or a warning about rules that are not yet reviewed. When a regulation changes, add a new version with the new date and values, check it with `Changes`, then click `Mark reviewed`. If Payroll reads from `Legal Basis`, that is all you do. If it reads from `Payroll Settings`, change the figures there too.

## Common questions

**Does changing the register change payroll amounts?** Only where a module reads from it: Offboarding, and Payroll or overtime if you chose Legal Basis in `Settings`. Otherwise it is a record.

**Can I switch back?** Yes, at any time.

## Related topics

- [[payroll]]
- [[bpjs]]
- [[pph21]]
- [[contracts]]
- [[offboarding]]

<!-- sources: routes/hr-offboarding.php, routes/web.php, routes/console.php, app/Http/Controllers/Finance/LegalBasisController.php, app/Http/Controllers/Finance/PayrollSettingsController.php, app/Services/Legal/LegalBasisService.php, app/Services/Legal/PayrollRatesFromLegal.php, app/Services/Overtime/OvertimeRateService.php, app/Console/Commands/LegalBasisReviewReminders.php, resources/views/legal/*.blade.php, resources/views/finance/legal-basis/settings.blade.php -->
