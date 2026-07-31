<?php
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
