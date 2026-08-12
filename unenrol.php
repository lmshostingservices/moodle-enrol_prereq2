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
require_once($CFG->dirroot . '/group/lib.php');

$ueid = required_param('ue', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$ue = $DB->get_record('user_enrolments', array('id' => $ueid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $ue->userid), '*', MUST_EXIST);
$instance = $DB->get_record('enrol', array('id' => $ue->enrolid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

$manager = has_capability('enrol/prereq2:manage', $context);
$unenrol = has_capability('enrol/prereq2:unenrol', $context);

if (!$unenrol) {
    print_error('canntunenrol', 'enrol');
}

if ($instance->enrol !== 'prereq2') {
    print_error('invalidenrolinstance', 'enrol');
}

$plugin = enrol_get_plugin('prereq2');

if (!$plugin->allow_unenrol($instance) && !$manager) {
    print_error('canntunenrol', 'enrol');
}

$returnurl = new moodle_url('/user/index.php', array('id' => $course->id));

if ($confirm && confirm_sesskey()) {
    $plugin->unenrol_user($instance, $ue->userid);
    redirect($returnurl);
}

$yesurl = new moodle_url($PAGE->url, array('confirm' => 1, 'sesskey' => sesskey()));
$message = get_string('unenrolconfirm', 'core_enrol', array('user' => fullname($user, true), 'course' => $course->fullname));

$PAGE->set_url('/enrol/prereq2/unenrol.php', array('ue' => $ueid));
$PAGE->set_title(get_string('unenrol', 'enrol'));
$PAGE->navbar->add(get_string('unenrol', 'enrol'));

echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $yesurl, $returnurl);
echo $OUTPUT->footer();
