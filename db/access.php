<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Who can configure the enrolment method in a course.
    'enrol/prereq2:config' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Who can enrol users manually.
    'enrol/prereq2:enrol' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Who can unenrol users.
    'enrol/prereq2:unenrol' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
