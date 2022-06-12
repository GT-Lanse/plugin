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
 * File description.
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
    'description' => 'Enabling into the API the course monitoring.',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  )
);
