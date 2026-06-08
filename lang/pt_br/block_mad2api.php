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
 * PT-BR Language strings for block_mad2api
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['apiurl'] = 'URL da API';
$string['descconfig'] = 'Descrição da seção de configuração';
$string['descfoo'] = 'Descrição de configuração';
$string['headerconfig'] = 'Cabeçalho da seção de configuração';
$string['labelfoo'] = 'Legenda da configuração';
$string['mad2api:addinstance'] = 'Adicionar LANSE';
$string['mad2api:context_course'] = 'Usar LANSE no contexto do curso';
$string['pluginname'] = 'Painel LANSE';
$string['error_modal_title'] = 'Erro ao habilitar curso';
$string['eventdashboardviewed'] = 'Painel LANSE visualizado';
$string['eventmonitoringdisabled'] = 'Monitoramento LANSE do curso desabilitado';
$string['eventmonitoringenabled'] = 'Monitoramento LANSE do curso habilitado';
$string['access_dashboard'] = 'Acessar painel';
$string['open_dashboard'] = 'Acessar plataforma LANSE';
$string['enable_dashboard'] = 'Habilitar monitoramento do curso';
$string['disable_dashboard'] = 'Desabilitar monitoramento do curso';
$string['load_data'] = 'Carregar dados do curso agora';
$string['error_modal_body'] = "
  <p>
    Por favor, verifique se o curso que você está tentando habilitar possui as datas de início e término configuradas corretamente. Se o problema persistir, entre em contato conosco em: <b>suporte.lanse@gmail.com</b>. Obrigado!
  </p>
";
$string['error_alert_body'] = 'Por favor, verifique se o curso que você está tentando habilitar possui as datas de início e término configuradas corretamente. Se o problema persistir, entre em contato conosco em: suporte.lanse@gmail.com. Obrigado!';

$string['privacy:metadata:external'] = 'Dados exportados para um serviço externo via API.';
$string['privacy:metadata:external:userid'] = 'O identificador do usuário enviado ao sistema externo.';
$string['privacy:metadata:external:courseid'] = 'O ID do curso associado aos dados enviados.';
$string['privacy:metadata:external:fullname'] = 'O nome completo do usuário.';
$string['privacy:metadata:external:email'] = 'O endereço de e-mail do usuário.';
$string['privacy:metadata:external:enrolments'] = 'Informações sobre as inscrições do usuário.';
$string['privacy:metadata:external:grades'] = 'As notas obtidas pelo usuário no curso.';
$string['privacy:metadata:external:progress'] = 'O progresso de conclusão do usuário no curso.';
$string['privacy:metadata:external:lastaccess'] = 'A data do último acesso do usuário ao curso.';

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

$string['nopermissiondashboard'] = 'Você não tem permissão para acessar o painel LANSE deste curso.';
$string['nopermissionmonitoring'] = 'Você não tem permissão para gerenciar o monitoramento LANSE deste curso.';
$string['dashboardnotenabled'] = 'O monitoramento está desabilitado para este curso.';
$string['mad2api:viewdashboard'] = 'Visualizar painel LANSE';
$string['mad2api:managemonitoring'] = 'Gerenciar monitoramento LANSE';

$string['confirm_enable_title'] = 'Habilitar monitoramento';
$string['confirm_enable_body'] = 'Tem certeza de que deseja habilitar o monitoramento do curso? Ao habilitar o monitoramento, você concorda que os dados do curso sejam enviados para a plataforma externa LANSE, que opera fora do ambiente do Moodle e é de responsabilidade de outro sistema.';

$string['confirm_disable_title'] = 'Desabilitar monitoramento';
$string['confirm_disable_body'] = 'Tem certeza de que deseja desabilitar o monitoramento do curso? Todos os dados históricos permanecerão disponíveis, mas o painel não será atualizado até que o monitoramento seja habilitado novamente.';

$string['confirm'] = 'Confirmar';
$string['cancel'] = 'Cancelar';
