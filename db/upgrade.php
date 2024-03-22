<?php
  defined('MOODLE_INTERNAL') || die();

  function xmldb_block_mad2api_upgrade($oldversion)
  {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    \block_mad2api\mad_dashboard::api_installation_call();

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