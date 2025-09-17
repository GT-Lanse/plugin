<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_mad2api', language 'pt_br'
 *
 * @package   block_mad2api
 * @copyright Daniel Neis <danielneis@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['apiurl'] = 'URL da API';
$string['descconfig'] = 'Descrição da seção de configuração';
$string['descfoo'] = 'Descrição de configuração';
$string['headerconfig'] = 'Cabeçalho da seção de configuração';
$string['labelfoo'] = 'Legenda da configuração';
$string['mad2api:addinstance'] = 'Adicionar LANSE';
$string['mad2api:myaddinstance'] = 'Adicionar LANSE to my moodle';
$string['pluginname'] = 'Painel LANSE';
$string['error_modal_title'] = 'Erro ao habilitar curso';
$string['access_dashboard'] = 'Acessar painel';
$string['open_dashboard'] = 'Acessar plataforma LANSE';
$string['enable_dashboard'] = 'Habilitar monitoramento do curso';
$string['disable_dashboard'] = 'Desabilitar monitoramento do curso';
$string['load_data'] = 'Carregar dados do curso agora';
$string['not_teacher'] = 'Para acessar o painel, você precisa ser professor do curso.';
$string['error_modal_body'] = "
  <p>
    Por favor, verifique se o curso que você está tentando habilitar possui as datas de início e término configuradas corretamente. Se o problema persistir, entre em contato conosco em: <b>suporte.lanse@gmail.com</b>. Obrigado!
  </p>
";
$string['error_alert_body'] = 'Por favor, verifique se o curso que você está tentando habilitar possui as datas de início e término configuradas corretamente. Se o problema persistir, entre em contato conosco em: suporte.lanse@gmail.com. Obrigado!';

$string['privacy:metadata:course_logs'] = 'Registros gerados pelo bloco para auditar interações com a API por usuário/curso.';
$string['privacy:metadata:course_logs:courseid'] = 'ID do curso relacionado ao registro.';
$string['privacy:metadata:course_logs:userid'] = 'ID do usuário que originou o evento/exportação.';
$string['privacy:metadata:course_logs:action'] = 'Ação realizada pela integração (ex.: habilitar, desabilitar, exportar).';
$string['privacy:metadata:course_logs:payload'] = 'Conteúdo enviado/recebido na chamada à API (pode conter dados pessoais).';
$string['privacy:metadata:course_logs:status'] = 'Status/resposta do processamento da API.';
$string['privacy:metadata:course_logs:createdat'] = 'Data/hora de criação do registro.';

$string['privacy:metadata:external'] = 'Este plugin envia dados para um serviço externo (API LANSE / MAD) para prover painéis e análises.';
$string['privacy:metadata:external:userid'] = 'Identificador do usuário no Moodle.';
$string['privacy:metadata:external:courseid'] = 'Identificador do curso no Moodle.';
$string['privacy:metadata:external:fullname'] = 'Nome completo do usuário.';
$string['privacy:metadata:external:email'] = 'Endereço de e-mail do usuário.';
$string['privacy:metadata:external:enrolments'] = 'Matrículas do usuário usadas para acesso e permissões no painel externo.';
$string['privacy:metadata:external:grades'] = 'Informações de notas usadas em análises.';
$string['privacy:metadata:external:progress'] = 'Informações de progresso/conclusão de atividades.';
$string['privacy:metadata:external:lastaccess'] = 'Carimbos de última visita usados para indicadores de atividade.';

$string['privacy:export:path'] = 'Dados do bloco MAD2 API';
