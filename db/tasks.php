<?php
defined('MOODLE_INTERNAL') || die();
$tasks = array(
  array(
    'classname' => 'block_mad2api\task\mad_logger',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
  )
);
