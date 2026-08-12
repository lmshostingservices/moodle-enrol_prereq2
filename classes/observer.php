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

namespace enrol_prereq2;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Triggered after a course is saved/updated in course edit form.
     * Stores or updates prerequisite in our table.
     *
     * @param \core\event\course_updated $event
 * @package    enrol_prereq2
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB;
        $courseid = $event->objectid;

        // Check if dropdown value was posted
        $prereqid = optional_param('enrol_prereq2_course', 0, PARAM_INT);

        if ($prereqid) {
            $record = $DB->get_record('enrol_prereq2', ['courseid' => $courseid]);
            if ($record) {
                $record->prereqid = $prereqid;
                $record->timemodified = time();
                $DB->update_record('enrol_prereq2', $record);
            } else {
                $record = (object)[
                    'courseid'     => $courseid,
                    'prereqid'     => $prereqid,
                    'timecreated'  => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('enrol_prereq2', $record);
            }
        } else {
            // If 0 (no prerequisite), optionally delete the row
            $DB->delete_records('enrol_prereq2', ['courseid' => $courseid]);
        }
    }
}
