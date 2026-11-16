---
key: microsoft-365-login-setup
chapter: getting-started
order: 53
title: Set up Microsoft 365 login
summary: How an EC Administrator connects EcoSystem to the company Microsoft 365 tenant so people can sign in with Microsoft.
requires: ["@role:EC Administrator"]
updated: 2026-11-15
---
This topic explains how to turn on and configure Sign in with Microsoft for the whole company. How people use it to sign in is in [[sign-in-and-security]].

## Who uses this

Only an EC Administrator. Other roles do not see this topic and do not see the `Microsoft 365 tenant configuration` box.

## Before you start

- You need access to the company app registration in Microsoft Entra, so you can add a redirect URI and read the tenant ID, the client ID and the client secret.
- Keep the client secret Value at hand. Microsoft shows it only once, when the secret is created.

## Step by step

Only an EC Administrator sees the `Microsoft 365 tenant configuration` box in the `Microsoft account` section of `Settings` on the `Security` tab.

![The Microsoft 365 tenant configuration box with the tenant ID, client ID, client secret, redirect URI and the enable option](images/guide/settings-microsoft-config.png)

*Screenshot with sample data.*

1. In Microsoft Entra, open the app registration of the company and add a `Web` redirect URI with the exact address shown in `Web redirect URI` (use `Copy`).
2. Make sure the app registration allows the basic sign-in permissions and that an administrator has approved them for the organization.
3. In EcoSystem fill in `Directory (tenant) ID`, `Application (client) ID` and `Client secret value`. Use the secret Value, not the secret ID.
4. Tick `Enable Microsoft 365 login` and select `Save M365 configuration`.

Leave `Client secret value` empty to keep the secret that is already stored. The secret is stored encrypted and is never shown again. Empty ID fields use the shared Microsoft app registration of the server, if there is one.

## Rules to know

- Only an EC Administrator can turn Microsoft sign-in on or change its settings. It is off until then.
- The tenant ID and the client ID must look like `00000000-0000-0000-0000-000000000000`. Login cannot be turned on while any of the three values is missing.
- The button `Sign in with Microsoft` appears on the sign-in page only when login is turned on and the tenant ID, client ID and client secret are all complete.
- Only accounts of the configured Microsoft organization can sign in this way.
- Every change of the configuration is written to the Audit Log. The client secret is never written there.
- Password sign-in stays available and unchanged, and two-step verification still applies after Microsoft sign-in.

## What happens next

After you save with `Enable Microsoft 365 login` ticked, people see `Sign in with Microsoft` on the sign-in page and can connect their own Microsoft account in `Settings`.

## Common questions

**The Sign in with Microsoft button is still not on the sign-in page.** Check that `Enable Microsoft 365 login` is ticked and that the tenant ID, client ID and client secret are all filled in.

**Can I see the stored client secret again?** No. It is encrypted and never shown. Enter a new Value to replace it.

<!-- sources: resources/views/settings/index.blade.php, app/Http/Controllers/MicrosoftAuthController.php, app/Support/MicrosoftLoginSettings.php, app/Http/Controllers/AuthController.php -->
