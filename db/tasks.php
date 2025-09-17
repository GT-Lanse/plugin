<?php
/**
 * List of scheduled tasks.
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
  ),

  array(
    'classname' => 'block_mad2api\task\mad_check_resend_data',
    'blocking' => 0,
    'minute' => '0',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
  )
);
