<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    [
        'eventname'   => '\core\event\course_updated',
        'callback'    => '\enrol_prereq2\observer::course_updated',
        'internal'    => false,
    ],
);
