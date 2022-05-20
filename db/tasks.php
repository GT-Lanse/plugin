<?php
defined('MOODLE_INTERNAL') || die();
$tasks = array(
    array(
        'classname' => 'block_mad2api\task\madLogger',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*/1',
        'month' => '*',
        'dayofweek' => '*',
    )
);
