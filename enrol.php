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

require('../../config.php');

$enrolid = required_param('id', PARAM_INT);
$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'prereq2'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login();

$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/enrol/prereq2/enrol.php', array('id' => $instance->id));

if (!enrol_is_enabled('prereq2')) {
    print_error('pluginnotinstalled', 'enrol_prereq2');
}

$plugin = enrol_get_plugin('prereq2');

if (isguestuser()) {
    print_error('noguestaccess', 'enrol');
}

if (is_enrolled($context, $USER)) {
    print_error('canntenrol', 'enrol_prereq2');
}

require_once("$CFG->dirroot/enrol/prereq2/classes/enrol_form.php");
$mform = new \enrol_prereq2\enrol_form(NULL, $instance);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    // Check prerequisites one more time
    if ($plugin->check_prerequisites($instance->courseid, $USER->id)) {
        $plugin->enrol_user($instance, $USER->id);
        redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    } else {
        print_error('prerequisitenotmet_generic', 'enrol_prereq2');
    }
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_prereq2'));

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();