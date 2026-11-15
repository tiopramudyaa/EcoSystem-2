---
key: forgot-password
chapter: getting-started
order: 52
title: Forgot password
summary: How to get back into your account when you forgot your password, with a reset link sent to your email.
requires: []
updated: 2026-11-12
---
If you forgot your password, you can set a new one yourself. EcoSystem sends a reset link to the email of your account. How to sign in normally is in [[sign-in-and-security]].

## Who uses this

Everyone who signs in to EcoSystem with a password.

## Before you start

- Know the email address saved in your EcoSystem account. The link is sent only to that address.
- Be able to open that mailbox now. The link is valid for 24 hours.

## Step by step

### Ask for a reset link

1. On the sign-in page select `Forgot password?`.
2. Type your email in `Registered Email` and select `Send Reset Link`.

![The Reset your password page with the Registered Email field and the Send Reset Link button](images/guide/forgot-password-request.png)

*Screenshot with sample data.*

3. The `Check Your Email` page appears and shows your email with part of it hidden.

![The Check Your Email page showing the hidden email address, the Reset My Password instruction and tips if the email does not arrive](images/guide/forgot-password-check-email.png)

*Screenshot with sample data.*

### Set a new password

1. Open the email and select `Reset My Password`.
2. On the `Set New Password` page type the new password in `New Password` and again in `Confirm Password`. The two ticks, `At least 8 characters` and `Passwords match`, must both turn green.
3. Select `Save Password`.

![The Set New Password page with both password fields filled and both requirement ticks green](images/guide/forgot-password-new-password.png)

*Screenshot with sample data.*

4. You return to the sign-in page with a message that the password was saved. Sign in with your new password.

### If the email does not arrive

1. Check the `Spam` or `Junk` folder.
2. Check that the address you typed is the one saved in your account.
3. Wait one minute, then select `Resend password reset link` on the `Check Your Email` page. It takes you back to the request page, where you send the request again.

## Rules to know

- A link is sent only if the email belongs to an active EcoSystem account. The page looks the same whether or not the email is known, so nobody can find out which emails are registered. If nothing arrives, the email may not be the one saved in your account. Ask an administrator.
- The link is valid for 24 hours and works only once. Asking for a new link makes the older one stop working.
- If you ask again within one minute, no second email is sent. After that minute you can ask again. From one place, no more than 5 requests per minute are accepted.
- The new password must have at least 8 characters, and the two entries must be the same.
- Saving the new password does not sign you in. You go to the sign-in page and sign in yourself.
- After the password is saved, every other session of your account ends, `Keep me signed in` is cleared, and browsers you had verified for two-step verification are no longer trusted. You sign in again on every device.
- Two-step verification stays on. If it is on for your account, the code is still asked when you sign in.
- Saving a new password is written to the Audit Log. The password itself is never written there.

## What happens next

After you save the password you sign in again. If you did not ask for the reset email, do not open the link, and tell an administrator.

## Common questions

**The link says it has expired.** Links last 24 hours and work once. Select `Forgot password?` and ask for a new one.

**`Save Password` is grey.** The button turns on only when the password has at least 8 characters and both entries match.

**I see a message about too many requests.** Wait one minute and try again.

**I keep getting locked out.** A password reset does not unlock an account that was locked for too many failed attempts. Wait for the lock to end, or ask an administrator.

**I sign in with Microsoft.** You can keep doing that. A password reset only changes your EcoSystem password.

<!-- sources: resources/views/auth/forgot-password.blade.php, resources/views/auth/check-email.blade.php, resources/views/auth/change-password.blade.php, routes/web.php, app/Http/Controllers/PasswordSetupController.php -->
