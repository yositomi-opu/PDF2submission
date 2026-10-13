<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$scan = required_param('scan', PARAM_ALPHANUM);
$token = '';
$action = optional_param('action', '', PARAM_ALPHA);
$course = get_course($courseid);
require_login($course);
require_capability('block/pdf2submissionbeta:manage', context_course::instance($courseid));
$url = new moodle_url('/blocks/pdf2submissionbeta/index.php', ['courseid' => $courseid, 'id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('pluginname', 'block_pdf2submissionbeta'));
$PAGE->set_heading(format_string($course->fullname));
$root = \block_pdf2submissionbeta\batch::load($scan, $courseid);
if (!$id && $root['assignments']) { $id = (int)array_key_first($root['assignments']); }
if ($id && !isset($root['assignments'][$id])) { throw new moodle_exception('invalidbatch','block_pdf2submissionbeta'); }
$token = $id ? $root['assignments'][$id]['token'] : '';
$url = new moodle_url('/blocks/pdf2submissionbeta/index.php', ['courseid'=>$courseid,'id'=>$id,'scan'=>$scan]);
$PAGE->set_url($url);
$assignment = null;
if ($id) {
    $assignment = \block_pdf2submissionbeta\service::assignment($id);
    if ((int)$assignment->get_course()->id !== $courseid) { throw new moodle_exception('invalidparameter'); }
}
if ($action === 'sheets' && $assignment) {
    require_sesskey();
    \block_pdf2submissionbeta\service::sheets($assignment)->Output('pdf2submissionbeta-sheets.pdf', 'D');
    exit;
}
if ($action === 'preview' && $assignment) {
    $batch = \block_pdf2submissionbeta\service::load($token, $id);
    $userid = required_param('userid', PARAM_INT);
    if (!isset($batch['groups'][$userid])) { throw new moodle_exception('invalidparameter'); }
    $students = \block_pdf2submissionbeta\service::students($assignment);
    if (!isset($students[$userid])) { throw new moodle_exception('invalidparameter'); }
    send_file(\block_pdf2submissionbeta\service::directory($token) . '/user-' . $userid . '.pdf',
        \block_pdf2submissionbeta\service::submission_filename($assignment, $students[$userid]), 0, 0, false, true);
    exit;
}
$notice = '';
if ($action === 'confirm' && $assignment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    // Release the browser session while Moodle saves submissions and runs observers.
    \core\session\manager::write_close();
    set_time_limit(0);
    \block_pdf2submissionbeta\service::confirm($assignment, $token, optional_param('overwrite', 0, PARAM_BOOL));
    // GET after POST prevents accidental resubmission on refresh.
    redirect(new moodle_url($url, ['batch' => $token]));
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_pdf2submissionbeta'));
if ($notice) { echo $OUTPUT->notification($notice, 'success'); }
echo html_writer::link(new moodle_url('/course/view.php',['id'=>$courseid]), get_string('backtocourse','block_pdf2submissionbeta'));
if ($root['errors']) { echo $OUTPUT->notification(get_string('unreadable','block_pdf2submissionbeta',implode(', ',$root['errors'])),'error'); }
echo html_writer::start_div('pdf2submissionbeta-review');
echo html_writer::start_tag('aside',['class'=>'pdf2submissionbeta-nav']);
echo $OUTPUT->heading(get_string('reviewassignments','block_pdf2submissionbeta'),3);
foreach ($root['assignments'] as $cmid=>$item) {
    // Re-check permission for each linked assignment before displaying its name.
    $other = \block_pdf2submissionbeta\service::assignment((int)$cmid);
    $state = \block_pdf2submissionbeta\service::load($item['token'],(int)$cmid);
    $label = format_string($other->get_instance()->name) . ($state['done'] ? ' ✓' : '');
    echo html_writer::tag('p',html_writer::link(new moodle_url($url,['id'=>$cmid]),$label,
        (int)$cmid===$id ? ['aria-current'=>'page'] : []));
}
echo html_writer::end_tag('aside');
echo html_writer::start_div('pdf2submissionbeta-main');
if ($assignment) {
    echo $OUTPUT->heading(format_string($assignment->get_instance()->name), 3);

    if ($token) {
        $batch = \block_pdf2submissionbeta\service::load($token, $id);
        if ($batch['done']) {
            echo $OUTPUT->notification(get_string('saved', 'block_pdf2submissionbeta', count($batch['groups'])), 'success');
        }
        $students = \block_pdf2submissionbeta\service::students($assignment);
        if ($batch['errors']) {
            echo $OUTPUT->notification(get_string('unreadable', 'block_pdf2submissionbeta', implode(', ', $batch['errors'])), 'error');
        }
        if (!empty($batch['inherited'])) {
            echo $OUTPUT->notification(get_string('inheritedpages', 'block_pdf2submissionbeta',
                implode(', ', $batch['inherited'])), 'warning');
        }
        if (!empty($batch['ignored'])) {
            echo $OUTPUT->notification(get_string('teacherignoredpages', 'block_pdf2submissionbeta',
                implode(', ', $batch['ignored'])), 'info');
        }
        $table = new html_table();
        $table->head = [get_string('fullname'), get_string('pages', 'block_pdf2submissionbeta'), 'PDF'];
        foreach ($batch['groups'] as $userid=>$pages) {
            $name = isset($students[$userid]) ? fullname($students[$userid]) : '?';
            $table->data[] = [s($name), s(implode(', ', $pages)), html_writer::link(new moodle_url($url,
                ['action'=>'preview', 'batch'=>$token, 'userid'=>$userid]), get_string('preview', 'block_pdf2submissionbeta'))];
        }
        echo html_writer::table($table);
        if (!$batch['done'] && !$batch['errors'] && $batch['groups']) {
            echo html_writer::tag('p', get_string('confirmnote', 'block_pdf2submissionbeta'));
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out_omit_querystring()]);
            foreach (['courseid'=>$courseid, 'id'=>$id, 'action'=>'confirm', 'scan'=>$scan, 'sesskey'=>sesskey()] as $key=>$value) {
                echo html_writer::empty_tag('input', ['type'=>'hidden', 'name'=>$key, 'value'=>$value]);
            }
            echo html_writer::tag('label', html_writer::empty_tag('input',
                ['type'=>'checkbox', 'name'=>'overwrite', 'value'=>1]) . ' ' .
                get_string('overwrite', 'block_pdf2submissionbeta'));
            echo html_writer::tag('p', get_string('overwritenote', 'block_pdf2submissionbeta'));
            echo html_writer::tag('button', get_string('confirm', 'block_pdf2submissionbeta'),
                ['type'=>'submit', 'class'=>'btn btn-primary']);
            echo html_writer::end_tag('form');
        }
    }

}
echo html_writer::end_div(); echo html_writer::end_div();
echo $OUTPUT->footer();
