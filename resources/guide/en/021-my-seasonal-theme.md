---
key: my-seasonal-theme
chapter: getting-started
order: 21
title: Seasonal theme
summary: Follow the festive look the administrator sets, turn it off or pick another theme for yourself, and see what each theme decorates.
requires: []
updated: 2026-11-20
---
Around a holiday, EcoSystem can show a festive look: decoration on the page, color accents in the sidebar and the header, and a themed loading screen after sign-in. The administrator chooses a default theme for everyone. In `Settings` you can follow it, turn it off, or pick another theme just for yourself.

## Who uses this

Every user. The `Seasonal theme` section is in `Settings` › `Appearance`.

## Before you start

- The choices are `Default`, `Off`, `Christmas`, `New Year`, `Ramadan`, `Eid (Lebaran)`, `Lunar New Year` and `Independence Day`. Theme names appear in your language (for example `Natal`, `Tahun Baru`, `Imlek` and `HUT RI` in Indonesian).
- `Theme` in `Settings` only means Light, Dark or Auto. The festive look is the separate `Seasonal theme` section.

## Step by step

### Choose your own theme

1. Open `Settings` › `Appearance`.
2. Find `Seasonal theme`. A line tells you which theme the administrator has active now.
3. Choose `Default` to follow the administrator, `Off` for no theme, or one theme for yourself.
4. Select `Save changes`.

### Turn the moving decoration on or off

1. In `Settings` › `Appearance`, switch `Show animations` on or off.
2. Select `Save changes`.

### Preview the loading screen of a theme

1. In `Settings` › `Appearance`, each theme card in `Seasonal theme` shows a small still image of its loading screen.
2. Select the theme card you want to look at.
3. Select `Play preview`. A full screen preview opens and plays the loading scene for a few seconds.
4. To leave early, select `Close preview`, press `Esc` or click anywhere on the preview. It also closes by itself after about six seconds.

## Rules to know

- **Order of priority.** Your own choice comes first. If you chose `Default`, you get the administrator's theme. If you chose `Off` or a specific theme, that wins over the administrator for you only.
- **Missing preferences.** People who have never saved their preferences follow `Default`, so they see the administrator's theme.
- **Separate login theme.** The administrator can set a separate theme for the sign-in pages and the loading screen. When it is set for the loading screen, it replaces your own choice for that screen only. The dashboard, sidebar, navbar and music keep following your choice or the global theme.
- **What a theme changes.** The decoration on the page, the colors of the sidebar, the header, buttons and links, and the loading screen after sign-in. Buttons that delete, reject or warn stay red so their meaning does not change.
- **Accent color.** While a seasonal theme is active for you, the colors of the sidebar, buttons and links follow the theme. The `Accent color` chosen in `Settings` shows in the preview but comes back after the page reloads. To keep your own accent color, set `Seasonal theme` to `Off`.
- **Background music.** Only the administrator chooses the track and the volume, and it applies to everyone. You cannot pick a track. You can only mute it on your own screen, and that choice is not saved for other users.
- **Loading screen.** After a successful sign-in, an animated 3D scene plays for about three seconds before the dashboard opens. Unless a separate login theme is set for the loading screen, it follows your theme, so if you chose your own theme you see that theme's scene, and if you chose `Off` you see none. Each theme has its own scene: Christmas shows Santa's sleigh flying over a snowy village at night, New Year shows fireworks over a city and its river, Ramadan shows a crescent moon over a domed mosque with hanging lanterns, Eid (Lebaran) shows a family greeting each other in a courtyard at dusk under hanging ketupat, Lunar New Year shows a golden dragon among lanterns, and Independence Day shows the Merah Putih flag rising while people salute.
- **Devices without 3D.** If the device or browser cannot draw 3D, or has the reduced motion setting on, a calm still version of the loading screen is shown instead and sign-in is never delayed. Low-power devices, or devices that start slowly, draw the scene at a lower quality so it stays smooth.
- **Navbar decoration.** Small ornaments sit in the two outer corners of the top bar and a few tiny pendants hang from its bottom edge, a different set for each theme: a pine branch with berries, bells and holly for Christmas; curling ribbons and firework bursts for New Year; a crescent moon, lanterns and a mosque silhouette for Ramadan; young coconut leaves and ketupat for Eid (Lebaran); a red lantern and plum blossom branch for Lunar New Year; and crossed flags with short pennants for Independence Day. They never cover the page title, buttons or text, cannot be clicked, do not change the size of the top bar and are hidden when printing. When `Show animations` is on and the device does not use reduced motion, each piece moves in its own way (swinging, twinkling, bursting, waving, glowing) and otherwise stays still. A thin 2 pixel line in the theme colors runs along the bottom edge of the top bar, and a few extra small elements (snowflakes, sparkles, stars or petals, depending on the theme) float near each outer corner; Christmas also gets a fine, faint frost edge under the top bar.
- **Dashboard accents.** On the Dashboard, a few quiet accents follow the theme as well: a pale hairline on the sidebar edge and cards, a thin snow cap on the active menu item and faint snow dots in the page background for Christmas; a gold shine line on the cards and a soft glint when you point at a summary card for New Year (only when `Show animations` is on); a very faint pattern in the sidebar header for Ramadan and Eid (Lebaran); gold corner brackets on the summary cards for Lunar New Year; and a red and white stripe on top of the top bar for Independence Day. These accents are decoration only: they never change text colors, tables, forms, charts or status colors, are not printed, and are not shown when the device asks for higher contrast.
- **Animations.** Moving decoration follows your `Show animations` setting and the reduced motion setting of your device. When either is off, the decoration stays still.
- **Loading screen preview.** `Play preview` is optional and works only when you press it. Until then the page downloads only the small still images on the theme cards, never the 3D files. The preview shows the scene of the theme card you selected (`Default` shows the administrator's current theme, and a theme with no loading screen, such as `Off`, cannot be previewed). It runs for at most about six seconds, can be closed earlier with `Close preview`, `Esc` or a click, and only one preview can be open at a time. It also stops when you switch to another tab or window. It does not sign you in, does not change your saved choice and uses no extra data from the server. If the device cannot draw 3D, Save-Data is on, the connection is slow (2G or 3G) or the reduced motion setting is on, it shows the still image with the same close controls instead.

## What happens next

After you save, your choice applies the next time a page loads. If you stay on `Default`, you follow the administrator's theme, and when the administrator changes it or a scheduled period starts or ends, you see the change without doing anything.

## Common questions

**I chose a theme but nothing changed for me.** Your own choice may be `Off` or another theme. Set `Seasonal theme` to `Default` to follow the administrator.

**Can I turn the music off for everyone?** No. You can only mute it on your own screen.

<!-- sources: app/Support/SeasonalThemes.php, app/Support/SeasonalThemeResolver.php, app/Http/Controllers/SettingsController.php, resources/views/settings/index.blade.php, resources/views/partials/seasonal-navbar.blade.php, resources/views/partials/seasonal-accents.blade.php, resources/views/partials/seasonal-preview.blade.php, lang/en/ui.php -->
