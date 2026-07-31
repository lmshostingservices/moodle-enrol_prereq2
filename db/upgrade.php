<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_prereq2_upgrade($oldversion) {
    if ($oldversion < 2026071500) {
        // Removed debug echo from course_updated() method.
        // Removed debug error_log() from observer.
        // Fixed restore_instance() operator precedence bug (!obj == const).
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['lib.php', 'version.php', 'db/upgrade.php', 'classes/observer.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_plugin_savepoint(true, 2026071500, 'enrol', 'prereq2');
    }
    // v1.1.0 — TRAINING-PLAN-SYNC: adds sync_access scheduled task + enablesync/pilotcohorts
    // settings. Students are kept active-enrolled only in their IP (In Progress) training-plan
    // units; everything else is suspended. Kill switch (enablesync) is OFF by default.
    // No DB schema change.
    if ($oldversion < 2026071600) {
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['version.php', 'settings.php', 'db/upgrade.php', 'db/tasks.php',
                      'classes/task/sync_access.php', 'lang/en/enrol_prereq2.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_plugin_savepoint(true, 2026071600, 'enrol', 'prereq2');
    }
    // v1.1.1 — HOTFIX: deleted-user guard + per-user error isolation in sync_access.
    // Defect A: JOIN {user} AND u.deleted=0 in the driving query so deleted users
    //           never reach enrol_user() / role_assign() which throws for deleted users.
    // Defect B: each user's reconciliation wrapped in try/catch so one bad record
    //           logs and continues instead of aborting the whole cron run.
    // No schema change.
    if ($oldversion < 2026072001) {
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['version.php', 'db/upgrade.php', 'classes/task/sync_access.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_plugin_savepoint(true, 2026072001, 'enrol', 'prereq2');
    }

    return true;
}
