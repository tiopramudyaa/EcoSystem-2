<?php

/*
 * Compliance (Legal sub menu) — My Compliance for every signed-in user, and Compliance Management that edits what it shows.
 * Everything that used to be a literal in the controllers, routes and views lives here, so one place changes it.
 * (Migrations keep their own literals on purpose: a migration must not change after it has run.)
 * Web Report is a separate module with its own config: config/web-report.php.
 */
return [

    // Menu Access slug. My Compliance has NO slug: every signed-in user may open it.
    'slugs' => [
        'manage' => 'legal.compliance',        // V open · C add · E change · D delete the portal content
    ],

    // A report sent from My Compliance gets its own code, so it is never mixed up with a public Web Report (WR-…).
    'report' => [
        'code_prefix' => 'CL',                 // CL-XXXX-XXXX
    ],

    'portal' => [
        'featured_limit' => 6,
        'upcoming_limit' => 3,
        'related_limit' => 4,
        'search_min_chars' => 2,
        'search_article_limit' => 30,
        'search_rule_limit' => 20,
    ],
];
