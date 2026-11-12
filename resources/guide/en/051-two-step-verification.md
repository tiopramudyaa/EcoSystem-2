---
key: two-step-verification
chapter: getting-started
order: 51
title: Two-step verification
summary: How to turn on two-step verification, enter your code when you sign in, use recovery codes and manage it in Settings.
requires: []
updated: 2026-11-12
---
Two-step verification adds a code from your phone to your sign-in, so a stolen password alone is not enough. This topic explains how to turn it on, how to enter the code when you sign in, and what to do if you lose your phone. How to sign in and change your password is in [[sign-in-and-security]].

## Who uses this

Everyone who signs in to EcoSystem. It is optional for most people, and an administrator can make it mandatory for a role. EC Administrator is mandatory by default.

## Before you start

- Install an authenticator app on your phone, for example Google Authenticator or Authy.
- Keep a place ready that only you can open, to save your recovery codes.

## Step by step

### Enter your verification code

If two-step verification is on for your account, EcoSystem asks for a code after your password or Microsoft sign-in.

![The Two-Factor Verification page asking for the 6-digit code from the authenticator app](images/guide/sign-in-2fa-code.png)

*Screenshot with sample data.*

1. Open your authenticator app and read the 6-digit code for EcoSystem.
2. Type it in the box and select `Verify`.

The code changes every 30 seconds, and each code works only once. If it is refused, wait for the next code and try again.

If you lost your phone, select `Use a recovery code instead` and enter one of the recovery codes you saved when you turned on two-step verification.

![The same page in recovery code mode, asking for a recovery code in the form XXXX-XXXX](images/guide/sign-in-2fa-recovery.png)

*Screenshot with sample data.*

Each recovery code works only once. Select `Use an authenticator code instead` to go back, or `Back to login` to start again.

### Turn on two-step verification

Two-step verification adds a code from your phone to your sign-in, so a stolen password alone is not enough.

1. In the `Security` tab, find `Two-factor authentication`. It shows `Not configured` until you set it up.
2. Select `Enable 2FA`.
3. Scan the QR code with your authenticator app. If you cannot scan, type the key shown under `Or enter this key manually`.
4. Type the 6-digit code that the app shows into the box and select `Confirm`.

![The two-factor setup with the QR code, the manual key, the code box and the Confirm and Cancel buttons](images/guide/settings-2fa-setup.png)

*Screenshot with sample data. The QR code and the key are hidden in this picture on purpose. Yours show real values, so never share them.*

5. EcoSystem shows 8 recovery codes. Save them somewhere safe that only you can open, tick `I've saved these recovery codes somewhere safe`, then select `Done`.

![The Save your recovery codes window with eight codes, the confirmation tick box and the Done button](images/guide/settings-2fa-recovery-codes.png)

*Screenshot with sample data. The codes are hidden in this picture on purpose.*

The recovery codes are shown only once. The `Done` button stays grey until you tick the box.

### Manage two-step verification

When it is on, the section shows `Enabled`.

![The Two-factor authentication section showing Enabled with the Regenerate recovery codes and Disable buttons](images/guide/settings-2fa-enabled.png)

*Screenshot with sample data.*

- `Regenerate recovery codes` makes a new set of 8 codes. Confirm, then enter your current password. The old codes stop working at once, so save the new ones.
- `Disable` turns two-step verification off. Confirm, then enter your current password.

## Rules to know

- Two-step verification is optional for most people. A role can be made mandatory by an administrator, and EC Administrator is mandatory by default. If it is mandatory for you and you have not set it up, every page takes you to `Settings` until you do. If you turn it off again, you are asked to set it up again.
- It is not active until you confirm a valid code. If you select `Cancel` or leave the page before that, it stays off. Selecting `Enable 2FA` again makes a new key and the old unconfirmed one is dropped.
- A code from the authenticator app works only once. A code you just used is refused, so wait for the next one.
- You get 8 recovery codes, shown once. Each works one time. Regenerating replaces the whole set and the old codes stop working.
- `Regenerate recovery codes` and `Disable` always ask for your current password.
- After your password or Microsoft sign-in, you have 5 minutes to enter the code. After that, sign in again.
- After 5 wrong codes in a row the account is locked for 30 minutes and administrators are alerted.
- When you enter a correct code, that browser is trusted for a limited time (normally 2 hours, the same as the length of a session). In that time you are not asked for the code again on that browser. The time starts again only when you enter a new code. Turning two-step verification off or on again clears this trust.
- `Keep me signed in` is ignored for accounts that use two-step verification. After your session ends, you sign in again with the code.
- An EC Administrator can reset the two-step verification of another person who lost their phone and recovery codes. It removes the key and the recovery codes, the person is told by email and in the app, and the reset is written to the Audit Log. Nobody can reset their own this way.
- Turning two-step verification on or off, and regenerating recovery codes, are written to the Audit Log. The key and the codes are never written there.
- Sign in with Microsoft does not skip two-step verification. The code is asked after Microsoft sign-in too.

## What happens next

After you turn it on, signing in asks for a code, with a password or with Microsoft, except on a browser you verified a short time ago. Keep your recovery codes safe, because they are the only way back in if you lose your phone.

## Common questions

**I do not receive a verification code.** The code comes from your authenticator app, not from email or SMS. Check that the time on your phone is correct, then try the next code.

**I lost my phone.** Sign in with your password, select `Use a recovery code instead` and enter one recovery code. Then turn two-step verification off and on again to link your new phone, and save the new recovery codes.

**I lost my phone and my recovery codes.** You cannot fix this yourself. Ask an EC Administrator to reset your two-step verification. You get an email and a notice in the app, then you sign in with your password only and set it up again.

<!-- sources: resources/views/auth/two-factor-challenge.blade.php, resources/views/settings/index.blade.php, routes/web.php, app/Http/Controllers/TwoFactorController.php, app/Http/Controllers/AuthController.php, app/Services/TwoFactorAuthService.php, app/Http/Middleware/EnforceTwoFactorForAdmins.php, config/security_center.php -->
