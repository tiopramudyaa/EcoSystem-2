---
key: sign-in-and-security
chapter: getting-started
order: 50
title: Sign in and account security
summary: How to sign in with a password or with Microsoft, change your password and keep your account safe.
requires: []
updated: 2026-11-12
---
This topic explains how to sign in to EcoSystem with your password or with your Microsoft work account, where to change your password, and the habits that keep your account safe.

## Who uses this

Everyone who signs in to EcoSystem. The part about setting up Microsoft 365 login is only for an EC Administrator.

## Before you start

- Have your own email or employee ID and your password ready. They belong to you alone.
- To sign in with Microsoft, you need your company Microsoft 365 work account, and Microsoft sign-in must be turned on by an EC Administrator.

## Step by step

### Sign in with your password

![The EcoSystem sign-in page with the email or employee ID field, the password field and the Sign In button](images/guide/sign-in-login.png)

*Screenshot with sample data.*

1. Open the EcoSystem address given by your company.
2. Enter your `Email or Employee ID` and your `Password`.
3. Tick `Keep me signed in` only on a computer that only you use.
4. Select `Sign In`.
5. If your account uses two-step verification, enter your code on the next page. The steps are in [[two-step-verification]].

### Sign in with Microsoft

If your company turned it on, the sign-in page shows `Sign in with Microsoft` under the `Sign In` button. It is an extra way to sign in. Your password keeps working.

![The sign-in page with the Sign in with Microsoft button below the Sign In button](images/guide/sign-in-microsoft.png)

*Screenshot with sample data.*

1. On the sign-in page select `Sign in with Microsoft`.
2. Choose your work account on the Microsoft page and finish the Microsoft sign-in.
3. You return to EcoSystem. If your account uses two-step verification, enter your code as usual (see [[two-step-verification]]).

The first time, EcoSystem matches your Microsoft work email to the email of your EcoSystem account and remembers the connection. If the email does not match, you cannot sign in this way until an administrator fixes your email, or until you connect the account yourself (see "Connect or disconnect your Microsoft account").

### If you forgot your password

Select `Forgot password?` on the sign-in page. The steps are in [[forgot-password]].

### Change your password

![The Security tab in Settings with the password form, Microsoft account, two-factor authentication and active sessions](images/guide/settings-security.png)

*Screenshot with sample data.*

1. Open `Settings` and choose the `Security` tab.
2. In the `Password` section enter the new password (at least 8 characters) and enter it again in `Confirm new password`.
3. Select `Update password`.

### Connect or disconnect your Microsoft account

Connect your Microsoft account once, and you can sign in with it afterwards.

1. Open `Settings` and choose the `Security` tab.
2. In the `Microsoft account` section select `Connect Microsoft account` and sign in with Microsoft.

![The Microsoft account section showing Not connected and the Connect Microsoft account button](images/guide/settings-microsoft-account.png)

*Screenshot with sample data.*

When it works, the section shows `Connected to Microsoft` with your Microsoft email and `Active`.

![The Microsoft account section showing Connected to Microsoft, the Microsoft email, Active and the Disconnect button](images/guide/settings-microsoft-connected.png)

*Screenshot with sample data.*

To remove the connection, select `Disconnect` and confirm. You can still sign in with your password.

If the section says `Sign in with Microsoft is not enabled yet`, an EC Administrator has not turned it on.

### Set up Microsoft 365 login (EC Administrator only)

Only an EC Administrator sees the `Microsoft 365 tenant configuration` box in the `Microsoft account` section.

![The Microsoft 365 tenant configuration box with the tenant ID, client ID, client secret, redirect URI and the enable option](images/guide/settings-microsoft-config.png)

*Screenshot with sample data.*

1. In Microsoft Entra, open the app registration of the company and add a `Web` redirect URI with the exact address shown in `Web redirect URI` (use `Copy`).
2. Make sure the app registration allows the basic sign-in permissions and that an administrator has approved them for the organization.
3. In EcoSystem fill in `Directory (tenant) ID`, `Application (client) ID` and `Client secret value`. Use the secret Value, not the secret ID.
4. Tick `Enable Microsoft 365 login` and select `Save M365 configuration`.

Leave `Client secret value` empty to keep the secret that is already stored. The secret is stored encrypted and is never shown again. Empty ID fields use the shared Microsoft app registration of the server, if there is one.

### Check where you are signed in

The `Active sessions` section of the `Security` tab lists the sessions of your account. The line marked `Current session` is the browser you are using now.

> [!IMPORTANT]
> Never share your password, your verification code or your recovery codes with anyone, including colleagues, your supervisor or someone who says they are from IT. Do not write them in chat, email or ticket notes.

> [!TIP]
> Sign out when you leave a shared computer.

## Rules to know

- Sign in with Microsoft is an extra option. Sign in with password stays available and unchanged.
- Only an EC Administrator can turn it on or change its settings. It is off until then. The button appears on the sign-in page only when it is turned on and the tenant ID, client ID and client secret are all complete.
- Only accounts of the configured Microsoft organization can sign in this way.
- EcoSystem never creates an account from a Microsoft sign-in. The Microsoft account must belong to an existing, active EcoSystem employee account.
- The first time, the Microsoft work email is matched to the email of the EcoSystem account. After that the connection is by the Microsoft account itself, so a changed email does not break it. An EcoSystem account that is already connected to one Microsoft account cannot be taken over by a different one.
- One Microsoft account can be connected to one EcoSystem user only, and one EcoSystem user to one Microsoft account.
- The usual checks still apply after Microsoft sign-in: the account must be active and finished its first password setup, must have access to EcoSystem, and cannot sign in while the system is locked or the account is locked for too many failed attempts. Customers cannot use EcoSystem and are sent to the customer portal.
- Two-step verification is not skipped. If it is on for your account, the code is asked after Microsoft sign-in, the same as after a password.
- `Keep me signed in` does not apply to Microsoft sign-in. Your session ends as a normal one.
- A Microsoft sign-in must be finished within 10 minutes after you select `Sign in with Microsoft`. After that, start again.
- Connecting and disconnecting a Microsoft account, and every change of the configuration, are written to the Audit Log. The client secret is never written there.
- The tenant ID and the client ID must look like `00000000-0000-0000-0000-000000000000`. Login cannot be turned on while any of the three values is missing.

## What happens next

Repeated failed sign-in attempts lock the account for a short time to protect it. Wait for the lock to end, or ask an administrator if you are still blocked.

## Common questions

**The Sign in with Microsoft button is not on my sign-in page.** Microsoft sign-in is not turned on yet. Ask an EC Administrator.

**I get a message that my Microsoft account cannot sign in.** The email of your Microsoft work account does not match an active EcoSystem account. Sign in with your password, connect your Microsoft account in `Settings`, or ask an administrator to check your email.

**I think someone else used my account.** Change your password at once and tell an administrator.

<!-- sources: resources/views/auth/login.blade.php, resources/views/settings/index.blade.php, routes/web.php, app/Http/Controllers/MicrosoftAuthController.php, app/Http/Controllers/AuthController.php, app/Support/MicrosoftLoginSettings.php, app/Support/MicrosoftIdToken.php -->
