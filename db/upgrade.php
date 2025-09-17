<?php
/**
 * Upgrade code for the mad2api block.
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_mad2api_upgrade($oldversion)
{
  global $CFG, $DB;

  $dbman = $DB->get_manager();

  \block_mad2api\mad_dashboard::api_installation_call();

  $table = new xmldb_table('mad2api_course_logs');

  if ($oldversion < 2024110612 && $dbman->table_exists($table)) {
      $field = new xmldb_field('students_sent', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);

      if (!$dbman->field_exists($table, $field->getName())) {
          $dbman->add_field($table, $field);
      }

      $field = new xmldb_field('last_log_page', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 1);

      if (!$dbman->field_exists($table, $field->getName())) {
          $dbman->add_field($table, $field);
      }
  }

  if ($oldversion < 2017011409) {
      // Define table mad2api_api_settings to be created.
      $table = new xmldb_table('mad2api_api_settings');

      // Adding fields to table mad2api_api_settings.
      $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
      $table->add_field('sent_at', XMLDB_TYPE_CHAR, '50', null, null, null, null);
      $table->add_field('created_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
      $table->add_field('updated_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

      // Adding keys to table mad2api_api_settings.
      $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

      // Conditionally launch create table for mad2api_api_settings.
      if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
      }

      // Mad2api savepoint reached.
      upgrade_block_savepoint(true, 2017011409, 'mad2api');

      $params = array(
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
      'sent_at' => date('Y-m-d H:i:s')
    );

    $DB->insert_record('mad2api_api_settings', $params, false);
  }

  if ($oldversion < 2024020532) {
    $dashboardSettings = $DB->get_records(
      "mad2api_dashboard_settings", array('is_enabled' => 1)
    );

    foreach ($dashboardSettings as $dashboardSetting) {
      $courseLog = $DB->get_record(
        "mad2api_course_logs", array('course_id' => $dashboardSetting->course_id)
      );

      if (!isset($courseLog->id)) {
        $params = array(
          'course_id' => $dashboardSetting->course_id,
          'status' => 'todo',
          'created_at' => date('Y-m-d H:i:s'),
          'updated_at' => date('Y-m-d H:i:s')
        );

        $DB->insert_record('mad2api_course_logs', $params, false);
      }
    }
  }

  if ($oldversion < 2024020551) {
    $dashboardSettings = $DB->get_records(
      "mad2api_dashboard_settings", array('is_enabled' => 1)
    );

    foreach ($dashboardSettings as $dashboardSetting) {
      \block_mad2api\mad_dashboard::api_send_students(
        $dashboardSetting->course_id
      );
    }
  }

  if ($oldversion == 2024020559) {
    $records = $DB->get_records(
      'mad2api_course_logs', array('status' => 'done')
    );

    foreach ($records as $record) {
      \block_mad2api\mad_dashboard::check_data_on_api($record->course_id);
    }
  }

  if ($oldversion < 2024110600) {
    $table = new xmldb_table('mad2api_dashboard_settings');
    $column = new xmldb_field('user_id');

    if ($dbman->field_exists($table, $column)) {
      $dbman->drop_field($table, $column);
    }

    $dashboardSettings = $DB->get_records(
      "mad2api_dashboard_settings", array('is_enabled' => 1)
    );

    foreach ($dashboardSettings as $dashboardSetting) {
      \block_mad2api\mad_dashboard::api_enable_call(
        $dashboardSetting->course_id
      );
    }
  }

  if (!!$DB->get_record("mad2api_api_settings", array())) {
    return true;
  }

  $params = array(
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'sent_at' => date('Y-m-d H:i:s')
  );

  $DB->insert_record('mad2api_api_settings', $params, false);

  return true;
}