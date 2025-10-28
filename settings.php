<?php
/**
 * Settings for the mad2api block.
 *
 * @package    block_mad2api
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$roles = $DB->get_records('role');
$roleOptions = array();

foreach ($roles as $role) {
    $role_name = role_get_name($role);
    $options[$role->id] = $role_name;
}

$settings->add(new admin_setting_heading('sampleheader',
                                         get_string('headerconfig', 'block_mad2api'),
                                         get_string('descconfig', 'block_mad2api')));

$settings->add(new admin_setting_configtext('mad2api/organization',
                                               'Nome da organização',
                                               '',
                                               'UFSC'));

$settings->add(new admin_setting_configtext('mad2api/api_url',
                                               'URL da API',
                                               '',
                                               'https://lanse.sites.ufsc.br/api'));

$settings->add(new admin_setting_configtext('mad2api/app_url',
                                               'URL da plataforma',
                                               '',
                                               'https://lanse.sites.ufsc.br'));

$apiKey = new admin_setting_configtext(
  'mad2api/api_key',
  'Chave API KEY Plugin',
  '',
  null
);

$apiKey->set_updatedcallback(function () {
  require_once('classes/mad_dashboard.php');

  \block_mad2api\mad_dashboard::api_installation_call();

  return true;
});

$settings->add($apiKey);

$settings->add(new admin_setting_configmultiselect(
  'mad2api/admin_roles',
  'Perfil Coordenador na LANSE',
  'Perfis que terão acesso ao Plugin como coordenador',
  array(1),
  $options
));

$settings->add(new admin_setting_configmultiselect(
  'mad2api/roles',
  'Perfil Professor/Tutor na LANSE',
  'Perfis que terão acesso ao Plugin como professor/tutor',
  array(4, 3),
  $options
));

$settings->add(new admin_setting_configselect(
  'mad2api/studentRole',
  'Selecione o papel de estudante na LANSE',
  'Perfil utilizado para identificar os estudantes no Plugin',
  5,
  $options
));
