---
key: cc-seasonal-theme
chapter: control-center
order: 27
title: Seasonal Theme
summary: Choose a festive look for EcoSystem, switch it on by hand or by a date schedule, set the background music, and see how each person can override it.
requires: [control-center.seasonal-theme]
updated: 2026-11-19
---
A seasonal theme gives EcoSystem a festive look for a holiday: decoration on the page, color accents in the sidebar and the header, and a themed loading screen after sign-in. An administrator chooses the default theme for everyone and for the sign-in page. Each person can still follow it, turn it off or pick another theme for themselves in `Settings`.

## Who uses this

Administrators. The `Control Center` › `Seasonal Theme` menu is meant for the administrator role only. Every other user chooses their own theme in `Settings` › `Appearance` (see the last section of this topic).

## Before you start

- The themes you can choose are `Off`, `Christmas`, `New Year`, `Ramadan`, `Eid (Lebaran)`, `Lunar New Year` and `Independence Day`. Their names appear in the language of each user (for example `Natal`, `Tahun Baru`, `Imlek` and `HUT RI` in Indonesian).
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

### Add your own music

1. On the same page, under `Upload a new track`, choose an audio file and select `Upload`.
2. After the upload, select the new file in the music list and select `Save settings` to make it active.

## Rules to know

- **Who can manage it.** Only users with the `Seasonal Theme` menu, which is the administrator role by default. Everyone else can only choose a theme for themselves in `Settings`.
- **Order of priority.** A person's own choice in `Settings` comes first. If they chose `Default`, they get the administrator's theme. If they chose `Off` or a specific theme, that wins over the administrator for that person only.
- **Manual mode.** The chosen theme is always on, on every day. Any periods you entered are kept but ignored.
- **Automatic schedule mode.** Each period has a theme, a start date and an end date. Both dates are included. On days outside every period, no theme is shown.
- **Overlapping periods.** If two periods cover the same day, the one listed first wins.
- **Limits and validation.** A schedule can have at most 20 periods. Each period needs a theme, a start date and an end date, and the end date must not be before the start date, otherwise the page shows an error and nothing is saved. A period cannot use `Off`.
- **Sign-in page.** There is no signed-in user yet, so the sign-in page itself always shows the administrator's theme (manual or by schedule).
- **What a theme changes.** The decoration on the page, the colors of the sidebar, the header, buttons and links, and the loading screen after sign-in. Buttons that delete, reject or warn stay red so their meaning does not change.
- **Loading screen.** After a successful sign-in, an animated 3D scene plays for about three seconds before the dashboard opens. It follows the theme of the person who signed in, so someone who chose their own theme sees that theme's scene, and someone who chose `Off` sees none. Each theme has its own scene: Christmas shows Santa's sleigh flying over a snowy village at night, New Year shows fireworks over a city and its river, Ramadan shows a crescent moon over a domed mosque with hanging lanterns, Eid (Lebaran) shows a family greeting each other in a courtyard at dusk under hanging ketupat, Lunar New Year shows a golden dragon among lanterns, and Independence Day shows the Merah Putih flag rising while people salute.
- **Devices without 3D.** If the device or browser cannot draw 3D, or has the reduced motion setting on, a calm still version of the loading screen is shown instead and sign-in is never delayed. Low-power devices, or devices that start slowly, draw the scene at a lower quality so it stays smooth.
- **Navbar decoration.** A decorative strip runs along the whole bottom edge of the top bar: a pine garland with ribbons and lights for Christmas, tinsel and streamers for New Year, crescent bunting and lanterns for Ramadan, woven ketupat for Eid (Lebaran), red lanterns for Lunar New Year and red and white pennants for Independence Day. It never covers buttons or text, cannot be clicked, does not change the size of the top bar and is hidden when printing. The lights on the Christmas, New Year and Ramadan strips twinkle only when `Show animations` is on and the device does not use reduced motion.
- **Animations.** Moving decoration follows each person's `Show animations` setting and the reduced motion setting of their device. When either is off, the decoration stays still.
- **Background music.** Only the administrator chooses the track and the volume, and it applies to everyone. Users cannot pick a track. A user can only mute it on their own screen, and that choice is not saved for other users.
- **Missing preferences.** People who have never saved their preferences follow `Default`, so they see the administrator's theme.
- **Accent color.** While a seasonal theme is active for a person, the colors of the sidebar, buttons and links follow the theme. The `Accent color` chosen in `Settings` shows in the preview but comes back after the page reloads. To keep their own accent color, a person sets `Seasonal theme` to `Off`.
- **Audit.** Saving the theme is recorded in the audit log.

## What happens next

After you save, people on `Default` see the new theme the next time a page loads. People who chose their own theme see no change. In automatic mode the theme starts and ends by itself on the dates you entered, without anyone saving again.

## Common questions

**I chose a theme but nothing changed for me.** Your own choice in `Settings` › `Appearance` › `Seasonal theme` may be `Off` or another theme. Set it to `Default` to follow the administrator.

**The schedule is on but no theme shows today.** Today is outside every period, or the mode is `Manual` with `Off` chosen. Check `Active today`, the dates (both ends are included) and the mode.

**Two periods overlap, which one shows?** The one listed first.

**Why does motion not appear?** `Show animations` may be off in `Settings` › `Appearance`, or the device uses the reduced motion setting.

**Can one person turn the music off for everyone?** No. A user can only mute it on their own screen.

### How each person chooses their own theme

1. Open `Settings` › `Appearance`.
2. Find `Seasonal theme`. A line tells you which theme the administrator has active now.
3. Choose `Default` to follow the administrator, `Off` for no theme, or one theme for yourself.
4. Select `Save changes`.

<!-- sources: routes/web.php, app/Support/SeasonalThemes.php, app/Support/GlobalSeasonalTheme.php, app/Support/SeasonalThemeResolver.php, app/Http/Controllers/SeasonalThemeSettingsController.php, app/Http/Controllers/SettingsController.php, resources/views/admin/seasonal-theme-settings.blade.php, resources/views/partials/seasonal-navbar.blade.php, resources/views/settings/index.blade.php, lang/en/ui.php -->
