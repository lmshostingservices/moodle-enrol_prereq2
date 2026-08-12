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

// Always start with this guard.
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Prerequisite2 enrolment';
$string['status'] = 'Enable this enrolment method';
$string['status_desc'] = 'Allow users to be enrolled via this method.';

$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'Message sent to users when they are enrolled by this method.';

$string['prerequisites'] = 'Prerequisite course';
$string['prerequisites_help'] = 'Select the course that must be completed before a user can be enrolled in this course.';

$string['warninghiddenusers'] = 'Some users were hidden because they have not completed the prerequisite course.';
$string['enrolmentblocked'] = 'This user cannot be enrolled because the prerequisite course has not been completed.';

// Capabilities (must match access.php).
$string['prereq2:config'] = 'Configure prerequisite enrolment instances';
$string['prereq2:enrol'] = 'Enrol users with prerequisite enrolment';
$string['prereq2:unenrol'] = 'Unenrol users with prerequisite enrolment';


$string['chooseprereq'] = 'Select prerequisite course';
$string['chooseprereq_help'] = 'Students must complete this course before enrolling.';
$string['errorchoosecourse'] = 'Please select a prerequisite course.';
$string['none'] = 'None';

// Training-plan sync task strings.
$string['task_sync_access']  = 'Sync course access to training plan';
$string['enablesync']        = 'Enable training-plan enrolment sync';
$string['enablesync_desc']   = 'When on, students are kept actively enrolled only in units currently In Progress (IP); all other units are suspended, and a missing IP enrolment is created automatically. REQUIRES the core "Cohort sync" scheduled task to be DISABLED, or a reactivation loop will occur.';
$string['pilotcohorts']      = 'Pilot cohorts (optional)';
$string['pilotcohorts_desc'] = 'Comma-separated cohort IDs to limit the sync during rollout. Empty = all cohorts.';

$string['privacy:metadata'] = 'The enrol_prereq2 plugin does not store any personal data.';
