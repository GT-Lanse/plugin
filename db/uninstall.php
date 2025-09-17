<?php
/**
 * Custom uninstall code for the mad2api block.
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_mad2api_uninstall() {
    global $DB;

    if ($DB->get_manager()->table_exists('mad2api_course_logs')) {

      $DB->delete_records('mad2api_course_logs');
    }

    if ($DB->get_manager()->table_exists('mad2api_dashboard_settings')) {
      $DB->delete_records('mad2api_dashboard_settings');
    }

    $DB->delete_records_select(
      'task_scheduled', "classname LIKE 'block_mad2api\\\\task\\\\mad_logger'"
    );
    $DB->delete_records_select(
      'task_scheduled', "classname LIKE 'block_mad2api\\\\task\\\\mad_check_resend_data'"
    );

    return true;
}
