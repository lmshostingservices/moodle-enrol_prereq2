<?php
namespace enrol_prereq2;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered after a course is saved/updated in course edit form.
     * Stores or updates prerequisite in our table.
     *
     * @param \core\event\course_updated $event
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
