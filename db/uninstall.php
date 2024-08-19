<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstallation procedure
 */
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
