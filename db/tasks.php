<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'enrol_prereq2\task\sync_access',
        'blocking'  => 0,
        'minute'    => '*/15',
        'hour'      => '*',
        'day'       => '*',
        'dayofweek' => '*',
        'month'     => '*',
    ],
];
