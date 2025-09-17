<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_mad2api_install()
{
  global $DB;

  $params = array(
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'sent_at' => date('Y-m-d H:i:s')
  );

  $DB->insert_record('mad2api_api_settings', $params, false);
}
