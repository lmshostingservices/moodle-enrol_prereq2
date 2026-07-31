<?php
namespace enrol_prereq2\task;

defined('MOODLE_INTERNAL') || die();

class sync_access extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_sync_access', 'enrol_prereq2');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        // Kill switch — OFF by default. Enable in plugin settings after piloting.
        if (!get_config('enrol_prereq2', 'enablesync')) {
            mtrace('enrol_prereq2 sync_access: disabled (enablesync off).');
            return;
        }
        if (!$DB->get_manager()->table_exists('block_trainingplan_userseq')) {
            mtrace('enrol_prereq2 sync_access: block_trainingplan_userseq missing — skipped.');
            return;
        }

        $active  = ENROL_USER_ACTIVE;    // 0
        $suspend = ENROL_USER_SUSPENDED; // 1

        // Optional pilot scoping: comma-separated cohort IDs. Empty = all.
        $params = ['s_active1' => $active];
        $cohortfilter = '';
        $pilot = trim((string)get_config('enrol_prereq2', 'pilotcohorts'));
        if ($pilot !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $pilot))));
            if ($ids) {
                list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'coh');
                $cohortfilter = " AND us.cohortid $insql ";
                $params += $inparams;
            }
        }

        // Find ONLY the (student, unit) rows whose access is wrong:
        //   IP but no active enrolment  -> needs activation or enrolment
        //   not IP but has active enrolment -> needs suspension
        //
        // FIX A (v1.1.1): JOIN {user} on AND u.deleted = 0 so that deleted or
        // nonexistent users are excluded from the result set entirely.
        // Moodle's role_assign() (called inside enrol_user()) throws a coding
        // exception for deleted users — without this guard one deleted-user row
        // aborts the entire task before any enrolment is written.
        $sql = "SELECT us.userid, us.courseid, us.outcome,
                       COUNT(ue.id) AS enrol_cnt,
                       SUM(CASE WHEN ue.status = :s_active1 THEN 1 ELSE 0 END) AS active_cnt
                  FROM {block_trainingplan_userseq} us
                  JOIN {user} u ON u.id = us.userid AND u.deleted = 0
             LEFT JOIN {enrol} e ON e.courseid = us.courseid
             LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = us.userid
                 WHERE 1=1 $cohortfilter
              GROUP BY us.userid, us.courseid, us.outcome
                HAVING (us.outcome =  'IP' AND active_cnt = 0)
                    OR (us.outcome <> 'IP' AND active_cnt > 0)";

        $rs = $DB->get_recordset_sql($sql, $params);
        $enrolled = 0; $activated = 0; $suspended = 0; $failures = 0;
        $studentroleid = $this->get_student_roleid();

        foreach ($rs as $row) {
            // FIX B (v1.1.1): Per-user error isolation. A single bad record logs
            // and continues rather than aborting the entire reconciliation run.
            try {
                // Belt-and-braces guard: if a deleted user somehow slipped
                // through (e.g. deleted between the driving query and here),
                // skip rather than let role_assign() throw.
                if (!$DB->record_exists('user', ['id' => $row->userid, 'deleted' => 0])) {
                    mtrace("  skip userid {$row->userid}: deleted or missing");
                    continue;
                }

                $ues = $DB->get_records_sql(
                    "SELECT ue.id, ue.status, e.id AS einstanceid, e.enrol AS method
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                      WHERE ue.userid = ? AND e.courseid = ?",
                    [$row->userid, $row->courseid]);

                if ($row->outcome === 'IP') {
                    if ($ues) {
                        // Reactivate any suspended enrolment(s).
                        foreach ($ues as $ue) {
                            if ((int)$ue->status !== $active) {
                                $inst = $DB->get_record('enrol', ['id' => $ue->einstanceid]);
                                enrol_get_plugin($inst->enrol)->update_user_enrol($inst, $row->userid, $active);
                                $activated++;
                            }
                        }
                    } else if ($studentroleid) {
                        // Self-heal: no enrolment at all -> enrol via a prereq2 instance.
                        $instance = $this->get_or_create_prereq2_instance($row->courseid);
                        if ($instance) {
                            enrol_get_plugin('prereq2')->enrol_user($instance, $row->userid, $studentroleid, 0, 0, $active);
                            $enrolled++;
                        }
                    }
                } else {
                    // Not IP -> suspend every active enrolment in this course.
                    foreach ($ues as $ue) {
                        if ((int)$ue->status !== $suspend) {
                            $inst = $DB->get_record('enrol', ['id' => $ue->einstanceid]);
                            enrol_get_plugin($inst->enrol)->update_user_enrol($inst, $row->userid, $suspend);
                            $suspended++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $failures++;
                mtrace("  ERROR userid {$row->userid} course {$row->courseid}: " . $e->getMessage());
            }
        }
        $rs->close();

        mtrace("enrol_prereq2 sync_access: enrolled {$enrolled}, activated {$activated}, suspended {$suspended}.");
        if ($failures > 0) {
            mtrace("sync_access completed with {$failures} skipped record(s).");
        }
    }

    /** Resolve the student role id (archetype 'student'). */
    private function get_student_roleid(): int {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => 'student']);
        if ($role) {
            return (int)$role->id;
        }
        $roles = get_archetype_roles('student');
        return $roles ? (int)reset($roles)->id : 0;
    }

    /** Get (or create) a prereq2 enrol instance on a course. */
    private function get_or_create_prereq2_instance(int $courseid) {
        global $DB;
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'prereq2']);
        if ($instance) {
            return $instance;
        }
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return null;
        }
        $plugin = enrol_get_plugin('prereq2');
        $id = $plugin->add_instance($course);
        return $id ? $DB->get_record('enrol', ['id' => $id]) : null;
    }
}
