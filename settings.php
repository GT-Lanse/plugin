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
    $rolename = role_get_name($role);
    $options[$role->id] = $rolename;
}

$settings->add(new admin_setting_heading('sampleheader',
                                         get_string('headerconfig', 'block_mad2api'),
                                         get_string('descconfig', 'block_mad2api')));

$settings->add(new admin_setting_configtext('block_mad2api/organization',
                                               get_string('organization', 'block_mad2api'),
                                               '',
                                               'UFSC'));

$settings->add(new admin_setting_configtext('block_mad2api/apiurl',
                                               get_string('apiurl', 'block_mad2api'),
                                               '',
                                               'https://lanse.sites.ufsc.br'));

$settings->add(new admin_setting_configtext('block_mad2api/appurl',
                                               get_string('appurl', 'block_mad2api'),
                                               '',
                                               'https://lanse.sites.ufsc.br'));

$apikey = new admin_setting_configtext(
    'block_mad2api/apikey',
    get_string('apikey', 'block_mad2api'),
    '',
    null
);

$apikey->set_updatedcallback(function () {
    require_once(__DIR__ . '/classes/mad_dashboard.php');

    // Saving the API key must succeed even when the API cannot be reached.
    \block_mad2api\mad_dashboard::guard(function () {
        \block_mad2api\mad_dashboard::api_installation_call();
    }, 'apikey updated callback');

    return true;
});

$settings->add($apikey);

$settings->add(new admin_setting_configmultiselect(
    'block_mad2api/adminroles',
    get_string('adminroles', 'block_mad2api'),
    get_string('adminroles_desc', 'block_mad2api'),
    array(1),
    $options
));

$settings->add(new admin_setting_configmultiselect(
    'block_mad2api/roles',
    get_string('roles', 'block_mad2api'),
    get_string('roles_desc', 'block_mad2api'),
    array(4, 3),
    $options
));

$settings->add(new admin_setting_configselect(
    'block_mad2api/studentrole',
    get_string('studentrole', 'block_mad2api'),
    get_string('studentrole_desc', 'block_mad2api'),
    5,
    $options
));

$settings->add(new admin_setting_heading('block_mad2api/timeoutsheading',
                                         get_string('timeouts_heading', 'block_mad2api'),
                                         get_string('timeouts_heading_desc', 'block_mad2api')));

$settings->add(new admin_setting_configtext(
    'block_mad2api/webconnecttimeout',
    get_string('webconnecttimeout', 'block_mad2api'),
    get_string('webconnecttimeout_desc', 'block_mad2api'),
    \block_mad2api\mad_dashboard::WEB_CONNECT_TIMEOUT,
    PARAM_INT
));

$settings->add(new admin_setting_configtext(
    'block_mad2api/webtimeout',
    get_string('webtimeout', 'block_mad2api'),
    get_string('webtimeout_desc', 'block_mad2api'),
    \block_mad2api\mad_dashboard::WEB_TIMEOUT,
    PARAM_INT
));

$settings->add(new admin_setting_configtext(
    'block_mad2api/cliconnecttimeout',
    get_string('cliconnecttimeout', 'block_mad2api'),
    get_string('cliconnecttimeout_desc', 'block_mad2api'),
    \block_mad2api\mad_dashboard::CLI_CONNECT_TIMEOUT,
    PARAM_INT
));

$settings->add(new admin_setting_configtext(
    'block_mad2api/clitimeout',
    get_string('clitimeout', 'block_mad2api'),
    get_string('clitimeout_desc', 'block_mad2api'),
    \block_mad2api\mad_dashboard::CLI_TIMEOUT,
    PARAM_INT
));
