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
 * Upgrade script.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_mad2api_upgrade($oldversion)
{
  global $CFG, $DB;

  $dbman = $DB->get_manager();

  \block_mad2api\mad_dashboard::api_installation_call();

  $table = new xmldb_table('block_mad2api_course_logs');

  if ($oldversion < 2024110612 && $dbman->table_exists($table)) {
      $field = new xmldb_field('studentssent', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0);

      if (!$dbman->field_exists($table, $field->getName())) {
          $dbman->add_field($table, $field);
      }

      $field = new xmldb_field('lastlogpage', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 1);

      if (!$dbman->field_exists($table, $field->getName())) {
          $dbman->add_field($table, $field);
      }
  }

  if ($oldversion < 2017011409) {
      // Define table block_mad2api_api_settings to be created.
      $table = new xmldb_table('block_mad2api_api_settings');

      // Adding fields to table block_mad2api_api_settings.
      $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
      $table->add_field('sentat', XMLDB_TYPE_CHAR, '50', null, null, null, null);
      $table->add_field('createdat', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
      $table->add_field('updatedat', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

      // Adding keys to table block_mad2api_api_settings.
      $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

      // Conditionally launch create table for block_mad2api_api_settings.
      if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
      }

      // Mad2api savepoint reached.
      upgrade_block_savepoint(true, 2017011409, 'mad2api');

      $params = array(
      'createdat' => date('Y-m-d H:i:s'),
      'updatedat' => date('Y-m-d H:i:s'),
      'sentat' => date('Y-m-d H:i:s')
    );

    $DB->insert_record('block_mad2api_api_settings', $params, false);
  }

  if ($oldversion < 2024020532) {
    $dashboardsettings = $DB->get_records(
      "block_mad2api_dashboard_settings", array('isenabled' => 1)
    );

    foreach ($dashboardsettings as $dashboardsetting) {
      $courseLog = $DB->get_record(
        "block_mad2api_course_logs", array('courseid' => $dashboardsetting->courseid)
      );

      if (!isset($courseLog->id)) {
        $params = array(
          'courseid' => $dashboardsetting->courseid,
          'status' => 'todo',
          'createdat' => date('Y-m-d H:i:s'),
          'updatedat' => date('Y-m-d H:i:s')
        );

        $DB->insert_record('block_mad2api_course_logs', $params, false);
      }
    }
  }

  if ($oldversion < 2024020551) {
    $dashboardsettings = $DB->get_records(
      "block_mad2api_dashboard_settings", array('isenabled' => 1)
    );

    foreach ($dashboardsettings as $dashboardsetting) {
      \block_mad2api\mad_dashboard::api_send_students(
        $dashboardsetting->courseid
      );
    }
  }

  if ($oldversion == 2024020559) {
    $records = $DB->get_records(
      'block_mad2api_course_logs', array('status' => 'done')
    );

    foreach ($records as $record) {
      \block_mad2api\mad_dashboard::check_data_on_api($record->courseid);
    }
  }

  if ($oldversion < 2024110600) {
    $table = new xmldb_table('block_mad2api_dashboard_settings');
    $column = new xmldb_field('user_id');

    if ($dbman->field_exists($table, $column)) {
      $dbman->drop_field($table, $column);
    }

    $dashboardsettings = $DB->get_records(
      "block_mad2api_dashboard_settings", array('isenabled' => 1)
    );

    foreach ($dashboardsettings as $dashboardsetting) {
      \block_mad2api\mad_dashboard::api_enable_call(
        $dashboardsetting->courseid
      );
    }
  }

  if ($oldversion < 2025112300) {
    $oldtable = new xmldb_table('mad2api_dashboard_settings');

    if ($dbman->table_exists($oldtable)) {
      $dbman->rename_table($oldtable, 'block_mad2api_dashboard_settings');
    }

    $table = new xmldb_table('block_mad2api_dashboard_settings');

    $field = new xmldb_field('created_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'createdat');
    }

    $field = new xmldb_field('updated_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'updatedat');
    }

    $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
        $dbman->rename_field($table, $field, 'courseid');
    }

    $field = new xmldb_field('is_enabled', XMLDB_TYPE_INTEGER, '2', null, null, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'isenabled');
    }
    $oldtable = new xmldb_table('mad2api_api_settings');

    if ($dbman->table_exists($oldtable)) {
      $dbman->rename_table($oldtable, 'block_mad2api_api_settings');
    }

    $table = new xmldb_table('block_mad2api_api_settings');

    $field = new xmldb_field('sent_at', XMLDB_TYPE_CHAR, '50', null, null, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'sentat');
    }

    $field = new xmldb_field('created_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'createdat');
    }

    $field = new xmldb_field('updated_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'updatedat');
    }

    $oldtable = new xmldb_table('mad2api_course_logs');

    if ($dbman->table_exists($oldtable)) {
      $dbman->rename_table($oldtable, 'block_mad2api_course_logs');
    }

    $table = new xmldb_table('block_mad2api_course_logs');

    $field = new xmldb_field('created_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'createdat');
    }

    $field = new xmldb_field('updated_at', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'updatedat');
    }

    $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'courseid');
    }

    $field = new xmldb_field('students_sent', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'studentssent');
    }

    $field = new xmldb_field('last_log_page', XMLDB_TYPE_INTEGER, '10', null, null, null, '1');

    if ($dbman->field_exists($table, $field)) {
      $dbman->rename_field($table, $field, 'lastlogpage');
    }

    // Savepoint
    upgrade_block_savepoint(true, 2025112300, 'mad2api');
  }

  if (!!$DB->get_record("block_mad2api_api_settings", array())) {
    return true;
  }

  $params = array(
    'createdat' => date('Y-m-d H:i:s'),
    'updatedat' => date('Y-m-d H:i:s'),
    'sentat' => date('Y-m-d H:i:s')
  );

  $DB->insert_record('block_mad2api_api_settings', $params, false);

  return true;
}