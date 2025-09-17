<?php
/**
 * LTI of LANSE dashboard view page.
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('classes/mad_dashboard.php');

$courseid   = required_param('courseid', PARAM_INT);
$coursename = optional_param('coursename', '', PARAM_TEXT);

require_login($courseid);
$context = context_course::instance($courseid, MUST_EXIST);

require_capability('block/mad2api:view', $context);

$course = get_course($courseid);
$coursefullname = format_string($course->fullname, true, ['context' => $context]);

$appUrl = get_config('mad2api', 'app_url');

if (empty($appUrl)) {
    echo $OUTPUT->header();

    \core\notification::error(get_string('configmissing', 'error') . ' (mad2api app_url)');

    echo $OUTPUT->footer();

    exit;
}

try {
    $response = \block_mad2api\mad_dashboard::api_enable_call($courseid);
} catch (\moodle_exception $e) {
    echo $OUTPUT->header();

    \core\notification::error('Falha ao contatar a API (erro Moodle).');

    debugging('mad2api api_enable_call moodle_exception: ' . $e->getMessage(), DEBUG_DEVELOPER);

    echo $OUTPUT->footer();

    exit;
} catch (\Throwable $e) {
    echo $OUTPUT->header();

    \core\notification::error('Falha ao contatar a API. Tente novamente mais tarde.');

    debugging('mad2api api_enable_call throwable: ' . $e->getMessage(), DEBUG_DEVELOPER);

    echo $OUTPUT->footer();

    exit;
}

if (!$response || !is_object($response)) {
    echo $OUTPUT->header();

    \core\notification::error('Resposta da API inválida.');

    echo $OUTPUT->footer();

    exit;
}

$token = $response->token ?? null;
$organizationid = isset($response->organizationId) ? (int)$response->organizationId : 0;
$apiCourseId = isset($response->courseId) ? (int)$response->courseId : 0;

if (empty($token)) {
    echo $OUTPUT->header();

    \core\notification::error('Token não recebido da API.');

    echo $OUTPUT->footer();

    exit;
}

$PAGE->set_url(new moodle_url('/blocks/mad2api/view.php', ['courseid' => $courseid]));
$PAGE->set_context($context);

if ((int)$CFG->version < 2022041900) {
    $PAGE->set_pagelayout('standard');
}

$PAGE->set_title("LANSE - Dashboard");
$PAGE->set_heading("Dashboard LANSE - Curso: " . $coursefullname);

echo $OUTPUT->header();

echo html_writer::tag('iframe', '', [
    'id' => 'lanseFrame',
    'src' => rtrim($appUrl, '/') . '/moodle/lti',
    'width' => '100%',
    'height' => '700',
    'style' => 'border:none;',
    'allowfullscreen' => 'true'
]);

$payload = [
    'type'           => 'auth',
    'token'          => $token,
    'courseId'       => (int)$apiCourseId,
    'organizationId' => (int)$organizationid
];

echo html_writer::script(
    '(function(){' .
        'var f=document.getElementById("lanseFrame");' .
        'if(!f){return;}' .
        'f.addEventListener("load",function(){' .
            'try{' .
                'f.contentWindow.postMessage(' . json_encode($payload, JSON_UNESCAPED_SLASHES) . ', ' . json_encode($appUrl, JSON_UNESCAPED_SLASHES) . ');' .
            '}catch(e){' .
                'console && console.error && console.error("Erro ao enviar postMessage para LANSE:", e);' .
            '}' .
        '});' .
    '})();'
);

echo $OUTPUT->footer();
