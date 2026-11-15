---
key: cc-notification-sounds
chapter: control-center
order: 26
title: Notification Sounds
summary: Upload and manage the alert sounds people can pick for their notifications.
requires: [control-center.sounds]
updated: 2026-11-02
---
`Notification Sounds` holds the audio files that employees can choose for their notification alerts. Administrators add new sounds here and remove ones nobody needs.

## Who uses this

Administrators with the EC Administrator role. The page checks this role itself, so another role that has the `Notification Sounds` menu still receives a "Superadmin only" message.

## Before you start

- Use short, calm sounds in `WAV`, `MP3`, `OGG` or `AAC`, up to 3 MB.
- Use sounds you are allowed to share. Do not upload copyrighted music.
- The sound marked `Default` is what people hear until they choose another. It cannot be deleted.

## Step by step

![The Notification Sounds page with an upload form and the list of available sounds](images/guide/cc-notification-sounds.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Notification Sounds`.
2. To add a sound, type a `Display Name` (for example `Soft Chime`), then click or drag an audio file into `Audio File` and choose `Upload Sound`.
3. Under `Available Sounds`, choose `Preview` to listen to a sound before people use it.
4. To remove a sound, choose `Delete` on its row and confirm. The default sound shows `Protected` and cannot be removed.

## What happens next

A new sound appears in the sound list in each person's notification settings. Delete only sounds nobody uses: people who picked a deleted sound must choose another one.

## Common questions

**The upload is rejected.** Check the format and that the file is 3 MB or smaller.

**Can I change which sound is the default?** Not from this page. The default sound is fixed.

<!-- sources: routes/web.php, app/Http/Controllers/AdminNotificationSoundController.php, resources/views/admin/sounds.blade.php -->
