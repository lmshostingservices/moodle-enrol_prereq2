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

defined('MOODLE_INTERNAL') || die();

class enrol_prereq2_plugin extends enrol_plugin {
    /**
     * Returns optional enrolment instance description text.
     * @param object $instance
     * @return string short html text
 * @package    enrol_prereq2
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public function get_description_text($instance) {
        if (!empty($instance->customtext1)) {
            return format_text($instance->customtext1, FORMAT_MOODLE, array('context' => context_course::instance($instance->courseid)));
        }
        return get_string('pluginname_desc', 'enrol_prereq2');
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context)) {
            return null;
        }

        // Check if the specific capability exists (might not be available during installation)
        $capabilities = get_all_capabilities();
        if (isset($capabilities['enrol/prereq2:config'])) {
            if (!has_capability('enrol/prereq2:config', $context)) {
                return null;
            }
        }

        return new moodle_url('/enrol/editinstance.php', array('courseid' => $courseid, 'type' => 'prereq2'));
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        global $DB;

        if ($fields === null) {
            $fields = array();
        }

        $fields['enrol'] = $this->get_name();
        $fields['courseid'] = $course->id;
        $fields['timecreated'] = time();
        $fields['timemodified'] = time();

        // Set defaults if not provided
        if (!isset($fields['status'])) {
            $fields['status'] = $this->get_config('status', ENROL_INSTANCE_ENABLED);
        }
        if (!isset($fields['name'])) {
            $fields['name'] = '';
        }
        if (!isset($fields['customtext1'])) {
            $fields['customtext1'] = '';
        }
        if (!isset($fields['customtext2'])) {
            $fields['customtext2'] = '';
        }

        // Handle prerequisites array from form
        if (isset($fields['prerequisites']) && is_array($fields['prerequisites'])) {
            $fields['customtext2'] = implode(',', $fields['prerequisites']);
            unset($fields['prerequisites']); // Remove from fields as it's not a database column
        }

        return $DB->insert_record('enrol', $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        global $DB;

        $properties = array('name', 'status', 'customtext1', 'customtext2');
        foreach ($properties as $property) {
            if (isset($data->$property)) {
                $instance->$property = $data->$property;
            }
        }
        
        // Handle prerequisites specially from form
        if (isset($data->prerequisites) && is_array($data->prerequisites)) {
            $instance->customtext2 = implode(',', $data->prerequisites);
        } else if (isset($data->prerequisites) && empty($data->prerequisites)) {
            $instance->customtext2 = '';
        }

        $instance->timemodified = time();

        return $DB->update_record('enrol', $instance);
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Get default values for instance fields
     * @return array
     */
    public function get_instance_defaults() {
        return array(
            'name' => '',
            'status' => $this->get_config('status', ENROL_INSTANCE_ENABLED),
            'customtext1' => '',
            'customtext2' => ''
        );
    }

    /**
     * Check if user can self enrol.
     * @param stdClass $instance enrolment instance
     * @param bool $checkuserenrolment if true will check if user enrolment is inactive.
     * @return bool|string true if successful, else error message or false
     */
    public function can_self_enrol(stdClass $instance, $checkuserenrolment = true) {
        global $USER;

        if ($checkuserenrolment) {
            if (isguestuser()) {
                return get_string('noguestaccess', 'enrol');
            }
            if (!is_enrolled(context_course::instance($instance->courseid), $USER, '', true)) {
                // Check prerequisites
                if (!$this->check_prerequisites($instance->courseid, $USER->id)) {
                    return $this->get_prerequisite_error_message($instance->courseid);
                }
            }
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('canntenrol', 'enrol_prereq2');
        }

        return true;
    }

    /**
     * Check if prerequisites are met for a user
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public function check_prerequisites($courseid, $userid) {
        global $DB;

        // Get prerequisite courses for this course
        $prerequisites = $this->get_course_prerequisites($courseid);
        
        if (empty($prerequisites)) {
            return true; // No prerequisites required
        }

        foreach ($prerequisites as $prereq_courseid) {
            if (!$this->is_course_completed($prereq_courseid, $userid)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get prerequisite courses for a course
     * @param int $courseid
     * @return array
     */
    public function get_course_prerequisites($courseid) {
        global $DB;

        $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'prereq2'), '*', IGNORE_MISSING);
        
        if (!$instance || empty($instance->customtext2)) {
            return array();
        }

        return explode(',', $instance->customtext2);
    }

    /**
     * Check if a user has completed a course
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public function is_course_completed($courseid, $userid) {
        global $DB;

        $completion = new completion_info(get_course($courseid));
        
        if (!$completion->is_enabled()) {
            // If completion is not enabled, check if user is enrolled and has grade
            $context = context_course::instance($courseid);
            if (!is_enrolled($context, $userid)) {
                return false;
            }
            
            // Check for passing grade
            $grade = grade_get_course_grade($userid, $courseid);
            return $grade && $grade->grade >= 50; // Assuming 50 is pass grade
        }

        return $completion->is_course_complete($userid);
    }

    /**
     * Get prerequisite error message
     * @param int $courseid
     * @return string
     */
    public function get_prerequisite_error_message($courseid) {
        global $DB, $USER;

        $prerequisites = $this->get_course_prerequisites($courseid);
        $missing_courses = array();

        foreach ($prerequisites as $prereq_courseid) {
            if (!$this->is_course_completed($prereq_courseid, $USER->id)) {
                $course = $DB->get_record('course', array('id' => $prereq_courseid));
                if ($course) {
                    $missing_courses[] = $course->fullname;
                }
            }
        }

        if (!empty($missing_courses)) {
            return get_string('prerequisitenotmet', 'enrol_prereq2', implode(', ', $missing_courses));
        }

        return get_string('prerequisitenotmet_generic', 'enrol_prereq2');
    }

    /**
     * Manual enrol user - with prerequisite check and warning
     * @param stdClass $instance
     * @param int $userid
     * @param int $roleid optional role id
     * @param int $timestart 0 means unknown
     * @param int $timeend 0 means unknown
     * @param int $status default to ENROL_USER_ACTIVE for new enrolments
     * @param bool $recovergrades restore grade history
     * @return void
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        global $DB;

        // Check if user has student role
        $context = context_course::instance($instance->courseid);
        $student_role = $DB->get_record('role', array('shortname' => 'student'));
        
        if ($roleid == $student_role->id) {
            if (!$this->check_prerequisites($instance->courseid, $userid)) {
                // This will be handled in the edit form with warning
                // For now, we'll still allow the enrollment but log it
                error_log("Warning: User {$userid} enrolled in course {$instance->courseid} without meeting prerequisites");
            }
        }

        parent::enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;

        if (has_capability("enrol/prereq2:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }

        if (has_capability("enrol/prereq2:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class'=>'editenrollink', 'rel'=>$ue->id));
        }

        return $actions;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;

        if ($step->get_task()->get_target() != backup::TARGET_NEW_COURSE) {
            // Only restore if this is a new course.
            return;
        }

        if ($instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => $this->get_name()))) {
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }

        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    // /**
    //  * Add elements to the edit instance form.
    //  *
    //  * @param stdClass $instance
    //  * @param MoodleQuickForm $mform
    //  * @param context $context
    //  * @return bool
    //  */
    // public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
    //     global $CFG, $DB;

    //     if (has_capability('enrol/prereq2:config', $context)) {
    //         $nameattribs = ['size' => '20', 'maxlength' => '255'];
    //         $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
    //         $mform->setType('name', PARAM_TEXT);
    //         $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

    //         $options = array(
    //             ENROL_INSTANCE_ENABLED  => get_string('yes'),
    //             ENROL_INSTANCE_DISABLED => get_string('no')
    //         );
    //         $mform->addElement('select', 'status', get_string('status', 'enrol_prereq2'), $options);
    //         $mform->addHelpButton('status', 'status', 'enrol_prereq2');

    //         // Prerequisites selection
    //         $prerequisite_options = $this->get_prerequisite_options($instance->courseid);
    //         if (!empty($prerequisite_options)) {
    //             $select = $mform->addElement('select', 'prerequisites', get_string('prerequisites', 'enrol_prereq2'), $prerequisite_options);
    //             $select->setMultiple(true);
    //             $mform->addHelpButton('prerequisites', 'prerequisites', 'enrol_prereq2');

    //             // Set current prerequisites
    //             if (!empty($instance->customtext2)) {
    //                 $mform->setDefault('prerequisites', explode(',', $instance->customtext2));
    //             }
    //         } else {
    //             $mform->addElement('static', 'no_courses', get_string('prerequisites', 'enrol_prereq2'), 
    //                               get_string('no_courses_available', 'enrol_prereq2'));
    //         }
    //     }

    //     // Course welcome message
    //     if (has_any_capability(['enrol/prereq2:config', 'moodle/course:editcoursewelcomemessage'], $context)) {
    //         $options = [
    //             'cols' => '60',
    //             'rows' => '8',
    //         ];
    //         $mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_prereq2'), $options);
    //         $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_prereq2');
    //     }

    //     // Enrolment changes warning
    //     if (has_capability('enrol/prereq2:config', $context) && enrol_accessing_via_instance($instance)) {
    //         $warntext = get_string('instanceeditselfwarningtext', 'core_enrol');
    //         $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warntext);
    //     }

    //     return true;
    // }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = array();
        
        // Add custom validation here if needed
        
        return $errors;
    }

    /**
     * Gets the course prerequisite options for dropdown
     * @param int $courseid
     * @return array
     */
    public function get_prerequisite_options($courseid) {
        global $DB;

        $courses = $DB->get_records('course', array('visible' => 1), 'fullname ASC');
        $options = array();

        foreach ($courses as $course) {
            if ($course->id != $courseid && $course->id != SITEID) {
                $options[$course->id] = $course->fullname;
            }
        }

        return $options;
    }
    public function can_hide_show_instance($instance) {
    // For most custom enrol plugins, this is safe to just return true.
    // Returning false means the eye icon (hide/show) won’t be shown in the enrol methods list.
    return true;
    }
    public function can_delete_instance($instance) {
        // For most custom enrol plugins, this is safe to just return true.
        // Returning false means the delete icon won’t be shown in the enrol methods list.
        return true;
    }
    // Add custom fields into the course settings form
    public function course_edit_form($instance, MoodleQuickForm $mform, $data, $context) {
        global $DB;

        // Add a header so you know it’s working
        $mform->addElement('header', 'enrol_prereq2_header', get_string('chooseprereq', 'enrol_prereq2'));

        // Get all available courses (excluding current one)
        $courses = $this->get_available_courses($instance->courseid);

        // Add "None" option at the top
        $courses = [0 => get_string('none')] + $courses;

        // Add dropdown for prerequisites
        $mform->addElement('select', 'enrol_prereq2_course',
            get_string('chooseprereq', 'enrol_prereq2'),
            $courses);

        $mform->addHelpButton('enrol_prereq2_course', 'chooseprereq', 'enrol_prereq2');

        // --- Preselect the saved prereq if one exists ---
        if (!empty($data->id)) { // Editing an existing course
            $record = $DB->get_record('enrol_prereq2', ['courseid' => $data->id]);
            if ($record) {
                $mform->setDefault('enrol_prereq2_course', $record->prereqid);
            } else {
                // Default to "None" if no record found
                $mform->setDefault('enrol_prereq2_course', 0);
            }
        } else {
            // Default to "None" for new instances
            $mform->setDefault('enrol_prereq2_course', 0);
        }
    }

    // Validation hook (optional, but recommended)
    public function course_edit_validation($instance, $data, $context) {
        $errors = array();
        if (!empty($data['enrol_prereq2_course']) && $data['enrol_prereq2_course'] == 0) {
            $errors['enrol_prereq2_course'] = get_string('errorchoosecourse', 'enrol_prereq2');
        }
        return $errors;
    }

    public function course_updated($inserted, $course, $data) {
        global $DB;
        $prereqid = optional_param('enrol_prereq2_course', 0, PARAM_INT);
        if ($prereqid) {
            $record = $DB->get_record('enrol_prereq2', ['courseid' => $course->id]);
            if ($record) {
                $record->prereqid = $prereqid;
                $record->timemodified = time();
                $DB->update_record('enrol_prereq2', $record);
            } else {
                $record = (object)[
                    'courseid' => $course->id,
                    'prereqid' => $prereqid,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('enrol_prereq2', $record);
            }
        } else {
            // If no prereq selected, remove the record
            $DB->delete_records('enrol_prereq2', ['courseid' => $course->id]);
        }
    }

    private function get_available_courses($currentcourseid) {
        global $DB;

        $sql = "SELECT id, fullname
                FROM {course}
                WHERE id > :minid
                AND id <> :currentid
                AND visible = 1
                ORDER BY fullname ASC";

        $params = [
            'minid' => 1,
            'currentid' => $currentcourseid
        ];

        return $DB->get_records_sql_menu($sql, $params);
    }

}