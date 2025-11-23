<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Uninstall function.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_mad2api_uninstall() {
    global $DB;

    if ($DB->get_manager()->table_exists('block_mad2api_course_logs')) {

      $DB->delete_records('block_mad2api_course_logs');
    }

    if ($DB->get_manager()->table_exists('block_mad2api_dashboard_settings')) {
      $DB->delete_records('block_mad2api_dashboard_settings');
    }

    $DB->delete_records_select(
      'task_scheduled', "classname LIKE 'block_mad2api\\\\task\\\\mad_logger'"
    );

    $DB->delete_records_select(
      'task_scheduled', "classname LIKE 'block_mad2api\\\\task\\\\mad_check_resend_data'"
    );

    return true;
}
