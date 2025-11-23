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
 * Settings for mad2api block.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$roles = $DB->get_records('role');

foreach ($roles as $role) {
    $role_name = role_get_name($role);
    $options[$role->id] = $role_name;
}

$settings->add(new admin_setting_heading('sampleheader',
                                         get_string('headerconfig', 'block_mad2api'),
                                         get_string('descconfig', 'block_mad2api')));

$settings->add(new admin_setting_configtext('block_mad2api/organization',
                                               'Nome da organização',
                                               '',
                                               'UFSC'));

$settings->add(new admin_setting_configtext('block_mad2api/apiurl',
                                               'URL da API',
                                               '',
                                               'https://lanse.sites.ufsc.br/api'));

$settings->add(new admin_setting_configtext('block_mad2api/appurl',
                                               'URL da plataforma',
                                               '',
                                               'https://lanse.sites.ufsc.br'));

$apikey = new admin_setting_configtext(
  'block_mad2api/apikey',
  'Chave API KEY Plugin',
  '',
  null
);

$apikey->set_updatedcallback(function () {
  require_once('classes/mad_dashboard.php');

  \block_mad2api\mad_dashboard::api_installation_call();

  return true;
});

$settings->add($apikey);

$settings->add(new admin_setting_configmultiselect(
  'block_mad2api/adminroles',
  'Perfil Coordenador na LANSE',
  'Perfis que terão acesso ao Plugin como coordenador',
  array(1),
  $options
));

$settings->add(new admin_setting_configmultiselect(
  'block_mad2api/roles',
  'Perfil Professor/Tutor na LANSE',
  'Perfis que terão acesso ao Plugin como professor/tutor',
  array(4, 3),
  $options
));

$settings->add(new admin_setting_configselect(
  'block_mad2api/studentrole',
  'Selecione o papel de estudante na LANSE',
  'Perfil utilizado para identificar os estudantes no Plugin',
  5,
  $options
));
