<?php
/**
 * List of external functions and services.
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
  'block_mad2api_enable_course' => array(
    'classpath' => '',
    'classname'   => 'block_mad2api\mad_dashboard',
    'methodname'  => 'enable',
    'description' => 'Enabling into the API the course monitoring.',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
  'block_mad2api_disable_course' => array(
    'classpath' => '',
    'classname'   => 'block_mad2api\mad_dashboard',
    'methodname'  => 'disable',
    'description' => 'Disabling into the API the course monitoring.',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
  'block_mad2api_load_course' => array(
    'classpath' => '',
    'classname'   => 'block_mad2api\mad_dashboard',
    'methodname'  => 'load',
    'description' => 'Loading course data on API.',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
);
