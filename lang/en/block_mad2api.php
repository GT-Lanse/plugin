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
 * English language strings for block_mad2api.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['apiurl'] = 'API URL';
$string['descconfig'] = 'Description of the config section';
$string['descfoo'] = 'Config description';
$string['headerconfig'] = 'Config section header';
$string['labelfoo'] = 'Config label';
$string['mad2api:addinstance'] = 'Add LANSE block';
$string['mad2api:context_course'] = 'Use LANSE in course context';

$string['mad2api:viewdashboard'] = 'View LANSE dashboard page';
$string['mad2api:managemonitoring'] = 'Enable or disable LANSE course monitoring';
$string['nopermissiondashboard'] = 'You do not have permission to access the LANSE dashboard for this course.';
$string['nopermissionmonitoring'] = 'You do not have permission to manage LANSE monitoring for this course.';
$string['pluginname'] = 'Dashboard LANSE';
$string['error_modal_title'] = 'Error on enabling course';
$string['access_dashboard'] = 'Access dashboard';
$string['open_dashboard'] = 'Open LANSE platform';
$string['enable_dashboard'] = 'Enable course monitoring';
$string['disable_dashboard'] = 'Disable course monitoring';
$string['load_data'] = 'Load course data now';
$string['error_modal_body'] = "
  <p>
    Please ensure that the course you are trying to enable has start date and end date configured correctly. If the problem persists, please contact us at: <b>suporte.lanse@gmail.com</b>. Thank you!
  </p>
";
$string['error_alert_body'] = 'Please ensure that the course you are trying to enable has start date and end date configured correctly. If the problem persists, please contact us at: suporte.lanse@gmail.com. Thank you!';

$string['privacy:metadata:course_logs'] = 'Records of course processing and synchronization status sent via API.';
$string['privacy:metadata:course_logs:courseid'] = 'The ID of the course whose data was processed.';
$string['privacy:metadata:course_logs:status'] = 'The status of the synchronization operation.';
$string['privacy:metadata:course_logs:studentssent'] = 'Information about the students sent to the external system.';
$string['privacy:metadata:course_logs:lastlogpage'] = 'The last log page processed for this specific course.';
$string['privacy:metadata:course_logs:createdat'] = 'The timestamp when this log entry was created.';
$string['privacy:metadata:course_logs:updatedat'] = 'The timestamp when this log entry was last updated.';

$string['privacy:metadata:external'] = 'Personal data exported to an external service via API.';
$string['privacy:metadata:external:userid'] = 'The unique identifier of the user sent to the external system.';
$string['privacy:metadata:external:courseid'] = 'The ID of the course associated with the exported data.';
$string['privacy:metadata:external:fullname'] = 'The full name of the user.';
$string['privacy:metadata:external:email'] = 'The email address of the user.';
$string['privacy:metadata:external:enrolments'] = 'Information regarding the user\'s enrolments.';
$string['privacy:metadata:external:grades'] = 'The grades obtained by the user in the course.';
$string['privacy:metadata:external:progress'] = 'The completion progress of the user within the course.';
$string['privacy:metadata:external:lastaccess'] = 'The timestamp of the user\'s last access to the course.';

$string['privacy:export:path'] = 'MAD2 API block data';

// view.php
$string['api_contact_error'] = 'Falha ao contatar a API (erro Moodle).';
$string['api_contact_error_retry'] = 'Falha ao contatar a API. Tente novamente mais tarde.';
$string['api_response_invalid'] = 'Resposta da API inválida.';
$string['api_token_missing'] = 'Token não recebido da API.';
$string['page_title'] = 'LANSE - Dashboard';
$string['page_heading'] = 'Dashboard LANSE - Curso: {$a->coursefullname}';
$string['post_message_error'] = 'Erro ao enviar postMessage para LANSE:';

// settings.php
$string['adminroles'] = 'Perfil Coordenador na LANSE';
$string['adminroles_desc'] = 'Perfis que terão acesso ao Plugin como coordenador';
$string['apikey'] = 'Chave API KEY Plugin';
$string['apiurl'] = 'URL da API';
$string['appurl'] = 'URL da plataforma';
$string['organization'] = 'Nome da organização';
$string['roles'] = 'Perfil Professor/Tutor na LANSE';
$string['roles_desc'] = 'Perfis que terão acesso ao Plugin como professor/tutor';
$string['studentrole'] = 'Selecione o papel de estudante na LANSE';
$string['studentrole_desc'] = 'Perfil utilizado para identificar os estudantes no Plugin';

// classes/task/mad_logger.php
$string['send_logs_task_name'] = 'Envio de Logs LANSE';
$string['check_resend_data_task_name'] = 'Verificação de Reenvio de Dados LANSE';

$string['dashboardnotenabled'] = 'Course monitoring is disabled for this course.';

$string['confirm_enable_title'] = 'Enable monitoring';
$string['confirm_enable_body'] = 'Are you sure you want to enable course monitoring? By enabling monitoring, you agree that course data will be sent to the external LANSE platform, which operates outside of the Moodle environment and is the responsibility of another system.';

$string['confirm_disable_title'] = 'Disable monitoring';
$string['confirm_disable_body'] = 'Are you sure you want to disable course monitoring?';

$string['confirm'] = 'Confirm';
$string['cancel'] = 'Cancel';
