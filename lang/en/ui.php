<?php

// Hanya grup tema musiman (diekstrak dari aldy_merge).
return [
    'seasonal' =>     array (
      'title' => 'Seasonal theme',
      'subtitle' => 'Festive colors and decoration on your dashboard',
      'follows_admin' => 'Default follows the choice of your administrator, currently :theme.',
      'opt_out' => 'Pick Off to turn it off for yourself only.',
      'manage' => 'Manage global default (Admin)',
      'default' => 'Default',
      'off' => 'Off',
      'accent_locked_title' => 'Accent color is controlled by the seasonal theme',
      'accent_locked_body' => 'While a seasonal theme is on, the interface uses the theme colors, so a different accent color will not show. Set Seasonal theme to Off to choose your own. Your saved color is kept and used again when the theme is off.',
      'accent_turn_off' => 'Turn off seasonal theme',
      'accent_toast' => 'A seasonal theme is active, so the accent color cannot be changed. Turn the seasonal theme off first.',
      'accent_custom' => 'Custom color',
      'accent_light_hint' => 'This color is very light. White button text may be hard to read.',
      'preview' => 
      array (
        'play' => 'Play preview',
        'close' => 'Close preview',
        'hint' => 'Plays a short preview of the loading screen for the selected theme. Nothing extra is downloaded until you press the button.',
        'unavailable' => 'Select a theme that has a loading screen to preview it.',
        'dialog_label' => 'Loading screen preview: :theme',
        'poster_alt' => 'Still image from the :theme loading screen',
        'still_note' => 'Showing a still image because the animation cannot run on this device or with your current settings.',
      ),
      'themes' => 
      array (
        'none' => 'Off',
        'natal' => 'Christmas',
        'tahun_baru' => 'New Year',
        'ramadan' => 'Ramadan',
        'lebaran' => 'Eid (Lebaran)',
        'imlek' => 'Lunar New Year',
        'hut_ri' => 'Independence Day',
      ),
      'loading' => 
      array (
        'preparing' => 'Preparing your EcoSystem workspace…',
        'natal' => 
        array (
          'title' => 'Merry Christmas from Eclectic Consulting',
          'subtitle' => 'Wishing you joy, peace, and a wonderful holiday season',
        ),
        'tahun_baru' => 
        array (
          'title' => 'Happy New Year from Eclectic Consulting',
          'subtitle' => 'Wishing you success and prosperity in the year ahead',
        ),
        'ramadan' => 
        array (
          'title' => 'Ramadan Kareem from Eclectic Consulting',
          'subtitle' => 'Wishing you a blessed and peaceful month of fasting',
        ),
        'lebaran' => 
        array (
          'title' => 'Eid Mubarak from Eclectic Consulting',
          'subtitle' => 'Wishing you and your family joy and forgiveness this Eid',
        ),
        'imlek' => 
        array (
          'title' => 'Happy Lunar New Year from Eclectic Consulting',
          'subtitle' => 'Wishing you good health, prosperity, and good fortune',
        ),
        'hut_ri' => 
        array (
          'title' => 'Happy Independence Day, Indonesia',
          'subtitle' => 'Eclectic Consulting celebrates together with you',
        ),
      ),
      'admin' => 
      array (
        'subtitle' => 'Set the festive theme for every user and the login page',
        'info' => 'This sets the default theme for every user\'s sidebar, navbar, and the login page. Users who picked their own option in Settings (Off or a specific theme) keep it. Only users on "Default" follow this setting.',
        'active_today' => 'Active today',
        'active_none' => 'No theme (Off)',
        'login_title' => 'Login and auth pages',
        'login_hint' => 'Theme for the sign in, forgot password, two-factor and change password pages, and for the loading screen shown right after sign in. Only administrators can change this. The dashboard, sidebar and navbar are not affected.',
        'login_theme' => 'Theme',
        'login_inherit' => 'Follow global theme',
        'login_off' => 'Off',
        'login_scope' => 'Applies to',
        'login_scope_both' => 'Login pages and loading screen',
        'login_scope_page' => 'Login pages only',
        'login_scope_loading' => 'Loading screen after sign in only',
        'login_scope_note' => 'When a theme other than "Follow global theme" is chosen, it replaces each user\'s own loading screen choice for the parts selected here.',
        'theme_title' => 'Theme',
        'theme_hint' => 'Applied to the sidebar, navbar, and login page.',
        'theme_manual' => 'The theme used in Manual mode.',
        'mode_title' => 'Activation',
        'mode_hint' => 'Choose whether the theme is switched on by you or by the calendar.',
        'music_title' => 'Background music',
        'music_hint' => 'Plays on every page after sign in. Only administrators choose the track; users can only mute or unmute for themselves.',
        'music_none' => 'None (no background music)',
        'preview' => 'Preview',
        'volume' => 'Volume',
        'volume_hint' => 'Applies to every user. They cannot change it, only mute or unmute.',
        'upload_title' => 'Upload a new track',
        'upload' => 'Upload',
        'upload_hint' => 'WAV, MP3, OGG, or AAC, up to 3 MB. Files share one folder with the sounds in Control Center › Notif Sounds. After uploading, select the track above and save.',
        'cancel' => 'Cancel',
        'save' => 'Save settings',
        'saved' => 'Seasonal theme saved. Users on "Default" see it immediately; users who picked their own are not affected.',
        'uploaded' => 'Uploaded ":file". Select it above and save to make it active.',
        'upload_type' => 'Only WAV, MP3, OGG, and AAC files are allowed.',
        'upload_size' => 'File size must not exceed 3 MB.',
      ),
      'mode' => 
      array (
        'manual' => 'Manual',
        'manual_hint' => 'The theme you pick above is always active.',
        'auto' => 'Automatic schedule',
        'auto_hint' => 'The theme follows the periods below. Outside every period the theme is Off.',
      ),
      'schedule' => 
      array (
        'title' => 'Schedule',
        'hint' => 'Used in Automatic schedule mode. If two periods overlap, the one listed first applies.',
        'theme' => 'Theme',
        'pick_theme' => 'Select a theme',
        'start' => 'Start date',
        'end' => 'End date',
        'add' => 'Add period',
        'remove' => 'Remove',
        'empty' => 'No periods yet. Add one to use the automatic schedule.',
        'suggested' => 'Suggested dates for :year are filled in. Adjust them if needed.',
        'moves' => 'The date of this occasion changes every year. Enter the exact dates.',
      ),
      'toasts' => 
      array (
        'choose_file' => 'Choose a file first.',
        'upload_failed' => 'Upload failed.',
        'upload_network' => 'Network error while uploading.',
        'schedule_incomplete' => 'Each period needs a theme, a start date, and an end date.',
        'schedule_order' => 'The end date must not be before the start date.',
      ),
    ),
];
