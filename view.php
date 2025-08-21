<?php
require('../../config.php');
require_once('classes/mad_dashboard.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$coursename = optional_param('coursename', '', PARAM_TEXT);

$response = \block_mad2api\mad_dashboard::api_enable_call($courseid);
$token = $response->token;
$organizationid = $response->organizationId;
$courseid = $response->courseId;

$PAGE->set_url(new moodle_url('/blocks/mad2api/view.php'));
$PAGE->set_context(context_system::instance());

if ((int)$CFG->version < 2022041900) {
    $PAGE->set_pagelayout('standard');
}

$PAGE->set_title("LANSE - Dashboard");
$PAGE->set_heading("Dashboard LANSE - Curso: " . $coursename);

echo $OUTPUT->header('Lanse Dashboard');

echo html_writer::tag('iframe', '', [
    'id' => 'lanseFrame',
    'src' => "https://app.lanse.com.br/moodle/lti",
    'width' => '100%',
    'height' => '700',
    'style' => 'border:none;',
    'allowfullscreen' => 'true'
]);

$payload = [
    'type'       => 'auth',
    'token'      => $token,
    'courseId'   => (int)$courseid,
    'organizationId' => (int)$organizationid
];
$origin = 'https://app.lanse.com.br/';

echo html_writer::script(
    '(function(){var f=document.getElementById("lanseFrame");f.addEventListener("load",function(){f.contentWindow.postMessage('
    . json_encode($payload)
    . ', '
    . json_encode($origin)
    . ');});})();'
);
?>

<?php

echo $OUTPUT->footer();
