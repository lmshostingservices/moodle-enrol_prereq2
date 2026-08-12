<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * enrol_prereq2 file.
 *
 * @package    enrol_prereq2
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

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
