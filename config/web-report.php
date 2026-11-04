<?php

/*
 * Web Report — anyone (no login) can report an issue at /web-report; the admin handles the reports in Control Center → Web Report
 * Management and sees a shortcut with the number of new reports in the top bar. Compliance (Legal) is a separate module: config/compliance.php.
 */
return [

    // Menu Access slug of Control Center → Web Report Management (V read the inbox · E change status / notes; no Create, no Delete).
    'slug' => 'control-center.web-reports',

    'follow_up_days' => 14,                    // the follow-up promised to the reporter
    'statuses' => ['new' => 'New', 'in_review' => 'In review', 'investigating' => 'Investigating', 'closed' => 'Closed'],

    // Other ways to report, shown on the public form and on My Compliance → Report a Concern (from the old lapor.eclectic.co.id site).
    'contact' => [
        'email' => 'compliance@eclectic.co.id',
        'phone' => '(031) 9953 2555',
        'address' => 'Eclectic Compliance Committee, Ciputra World, Skyloft Building Unit 1678, Jl. Mayjen Sungkono No. 89, Gn. Sari, Dukuhpakis, Surabaya',
        'paper_form' => 'downloads/anti-bribery-corruption-report-form.pdf',   // under public/
        'paper_deadline_days' => 30,                                    // working days after the bribe / corruption was received
    ],

    'code_prefix' => 'WR',                     // WR-XXXX-XXXX
    'code_alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',   // no 0/O/1/I: the code is read out and typed by hand

    'disk' => 'local',                         // PRIVATE disk: evidence is never in a public folder
    'directory' => 'web-report',
    'evidence_types' => 'pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,csv,txt,mp3,m4a,mp4,mov,zip',
    'evidence_max_kb' => 15360,

    'chronology_min' => 20,
    'chronology_max' => 20000,

    // Public form protection (it is open to everyone): attempts,minutes
    'throttle_submit' => '5,10',
    'throttle_status' => '10,1',
    'captcha_range' => [2, 12],                // the two numbers of the one-time sum

    'languages' => ['en', 'id'],
    'default_language' => 'en',
    'per_page' => 20,
];
