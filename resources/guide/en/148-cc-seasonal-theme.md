---
key: cc-seasonal-theme
chapter: control-center
order: 27
title: Seasonal Theme
summary: Choose a festive look for EcoSystem, switch it on by hand or by a date schedule, set the background music, and see how each person can override it.
requires: [control-center.seasonal-theme]
updated: 2026-11-23
---
A seasonal theme gives EcoSystem a festive look for a holiday: decoration on the page, color accents in the sidebar and the header, and a themed loading screen after sign-in. An administrator chooses the default theme for everyone and for the sign-in page. Each person can still follow it, turn it off or pick another theme in `Settings`.

## Who uses this

Administrators. The `Control Center` › `Seasonal Theme` menu is meant for the administrator role only. Every other user chooses their own theme in `Settings` › `Appearance` (see [[my-seasonal-theme]]).

## Before you start

- The themes you can choose are `Off`, `Christmas`, `New Year`, `Ramadan`, `Eid (Lebaran)`, `Lunar New Year` and `Independence Day`. Their names follow each user's language (for example `Natal` and `HUT RI` in Indonesian).
- Decide whether the theme should be always on (manual) or follow dates (automatic schedule).
- The dates of Ramadan, Eid and Lunar New Year change every year, so enter the exact dates yourself in the schedule.
- To use your own music, have an audio file ready (WAV, MP3, OGG or AAC, up to 3 MB).

## Step by step

### Set the default theme

1. Open `Control Center` › `Seasonal Theme`.
2. Under `Activation`, choose the mode:
   - `Manual`: the theme you choose is always on.
   - `Automatic schedule`: the theme follows the date ranges you list.
3. In manual mode, choose the theme card you want under `Theme`. Choose `Off` for no theme. The line `Active today` shows which theme is on right now.
4. In automatic mode, use `Schedule` and `Add period` for each period: choose the theme, the `Start date` and the `End date`. Use `Remove` to delete a period. You can add up to 20 periods.
5. Under `Background music`, choose a track (or `None (no background music)`), use `Preview` to listen, and set the `Volume`.
6. Select `Save settings`.

### Set a separate theme for the login pages and loading screen

1. On the same page, find the card `Login and auth pages`.
2. Under `Theme`, choose `Follow global theme` (the default), `Off`, or one theme.
3. Under `Applies to`, choose `Login pages and loading screen`, `Login pages only` or `Loading screen after sign in only`.
4. Select `Save settings`.

### Preview the loading screen of a theme

1. Open `Control Center` › `Seasonal Theme` (or `Settings` › `Appearance` for your own theme). Each theme card shows a small still image of its loading screen.
2. Select the theme card you want to look at.
3. Select `Play preview`. A full screen preview opens and plays the loading scene for a few seconds.
4. To leave early, select `Close preview`, press `Esc` or click the preview. It closes by itself after about six seconds.

### Add your own music

1. On the same page, under `Upload a new track`, choose an audio file and select `Upload`.
2. After the upload, select the new file in the music list and select `Save settings` to make it active.

## Rules to know

- **Who can manage it.** Only users with the `Seasonal Theme` menu, which is the administrator role by default. Everyone else can only choose a theme for themselves in `Settings`.
- **Order of priority.** A person's own choice in `Settings` comes first. If they chose `Default`, they get the administrator's theme. If they chose `Off` or a specific theme, that wins over the administrator for that person only.
- **Manual mode.** The chosen theme is always on. Any periods you entered are kept but ignored.
- **Automatic schedule mode.** Each period has a theme, a start date and an end date. Both dates are included. On days outside every period, no theme is shown.
- **Overlapping periods.** If two periods cover the same day, the one listed first wins.
- **Limits and validation.** A schedule can have at most 20 periods. Each period needs a theme, a start date and an end date, and the end date must not be before the start date, otherwise the page shows an error and nothing is saved. A period cannot use `Off`.
- **Sign-in page.** There is no signed-in user yet, so the sign-in page shows the administrator's global theme (manual or by schedule), unless the `Login and auth pages` card sets a separate theme for it. While a theme is on, the left panel shows an illustration of that theme (a decorated tree, a clock tower at midnight under fireworks, a mosque with lanterns, ketupat with a mosque and drum, a dragon with lanterns, or the flag with the Monas) and the `Sign In` button takes the theme color. Each theme keeps one color family across the sign-in page, loading screen and dashboard. The text stays readable, the form is unchanged, and with `Off` or on a phone no picture is downloaded. Gentle motion, such as falling snow, plays only when the device does not ask for reduced motion.
- **Separate login theme.** The `Login and auth pages` card has a theme (`Follow global theme`, `Off` or one theme) and a scope. With `Follow global theme` nothing changes: the pages follow the global theme and the loading screen follows each person's own theme. With another choice, `Login pages and loading screen` applies it to both, `Login pages only` applies it to the sign-in, forgot password, two-factor and change password pages and leaves the loading screen as before, and `Loading screen after sign in only` applies it to the loading screen and leaves the pages on the global theme. Choosing `Off` here turns the theme off for the selected parts.
- **Who can change the login theme.** Only users with the `Seasonal Theme` menu. People cannot change it in `Settings`. A login theme set for the loading screen replaces each person's own choice for that screen only; the dashboard, sidebar, navbar and music are not affected. The loading screen appears only after a password sign in. The two-factor step and signing in with Microsoft go straight to the dashboard without it.
- **Loading screen.** After a password sign in, an animated 3D scene plays for about three seconds before the dashboard opens. Unless a separate login theme is set for the loading screen, it follows the theme of the person who signed in, and someone who chose `Off` sees none. Buttons that delete, reject or warn stay red under any theme. See [[my-seasonal-theme]].
- **Loading screen preview.** `Play preview` is optional and works only when you press it. It shows the scene of the selected theme card (`Default` shows the current global theme, and `Off` cannot be previewed), runs for at most about six seconds, and closes with `Close preview`, `Esc` or a click. It does not sign you in or change your saved choice. On a device that cannot draw 3D, with Save-Data on, on a slow connection or with reduced motion, it shows the still image instead. Details for people using it from `Settings` are in [[my-seasonal-theme]].
- **Background music.** Only the administrator chooses the track and the volume, and it applies to everyone. A user can only mute it on their own screen, and that choice is not saved for other users.
- **Audit.** Saving the theme is recorded in the audit log.

## What happens next

After you save, people on `Default` see the new theme the next time a page loads. People who chose their own theme see no change. In automatic mode the theme starts and ends by itself on the dates you entered.

## Common questions

**I chose a theme but nothing changed for me.** Your own choice in `Settings` › `Appearance` may be `Off` or another theme. Set it to `Default` to follow the administrator.

**The schedule is on but no theme shows today.** Today is outside every period, or the mode is `Manual` with `Off` chosen. Check `Active today`, the dates (both ends are included) and the mode.

**Two periods overlap, which one shows?** The one listed first.


<!-- sources: routes/web.php, app/Support/SeasonalThemes.php, app/Support/GlobalSeasonalTheme.php, app/Support/SeasonalThemeResolver.php, app/Http/Controllers/SeasonalThemeSettingsController.php, app/Http/Controllers/SettingsController.php, resources/views/admin/seasonal-theme-settings.blade.php, resources/views/partials/seasonal-navbar.blade.php, resources/views/settings/index.blade.php, resources/views/partials/seasonal-preview.blade.php, lang/en/ui.php -->
