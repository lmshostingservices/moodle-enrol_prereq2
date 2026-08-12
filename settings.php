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

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'enrol_prereq2/enablesync',
        get_string('enablesync', 'enrol_prereq2'),
        get_string('enablesync_desc', 'enrol_prereq2'),
        0));

    $settings->add(new admin_setting_configtext(
        'enrol_prereq2/pilotcohorts',
        get_string('pilotcohorts', 'enrol_prereq2'),
        get_string('pilotcohorts_desc', 'enrol_prereq2'),
        '', PARAM_SEQUENCE));
}
