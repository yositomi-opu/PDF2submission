<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$token = optional_param('batch', '', PARAM_ALPHANUM);
$action = optional_param('action', '', PARAM_ALPHA);
$course = get_course($courseid);
require_login($course);
require_capability('block/pdf2submission:manage', context_course::instance($courseid));
$url = new moodle_url('/blocks/pdf2submission/index.php', ['courseid' => $courseid, 'id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('pluginname', 'block_pdf2submission'));
$PAGE->set_heading(format_string($course->fullname));
$assignment = null;
if ($id) {
    $assignment = \block_pdf2submission\service::assignment($id);
    if ((int)$assignment->get_course()->id !== $courseid) { throw new moodle_exception('invalidparameter'); }
}
if ($action === 'sheets' && $assignment) {
    require_sesskey();
    \block_pdf2submission\service::sheets($assignment)->Output('pdf2submission-sheets.pdf', 'D');
    exit;
}
if ($action === 'preview' && $assignment) {
    $batch = \block_pdf2submission\service::load($token, $id);
    $userid = required_param('userid', PARAM_INT);
    if (!isset($batch['groups'][$userid])) { throw new moodle_exception('invalidparameter'); }
    $students = \block_pdf2submission\service::students($assignment);
    if (!isset($students[$userid])) { throw new moodle_exception('invalidparameter'); }
    send_file(\block_pdf2submission\service::directory($token) . '/user-' . $userid . '.pdf',
        \block_pdf2submission\service::submission_filename($assignment, $students[$userid]), 0, 0, false, true);
    exit;
}
$notice = '';
if ($action === 'confirm' && $assignment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    // Release the browser session while Moodle saves submissions and runs observers.
    \core\session\manager::write_close();
    set_time_limit(0);
    \block_pdf2submission\service::confirm($assignment, $token, optional_param('overwrite', 0, PARAM_BOOL));
    // GET after POST prevents accidental resubmission on refresh.
    redirect(new moodle_url($url, ['batch' => $token]));
}
$form = $assignment ? new \block_pdf2submission\form\upload($url, ['courseid'=>$courseid, 'id'=>$id]) : null;
if ($form && $form->get_data()) {
    $token = bin2hex(random_bytes(16));
    $directory = \block_pdf2submission\service::directory($token);
    mkdir($directory, 0700);
    $form->save_file('scan', $directory . '/source.pdf');
    \core\session\manager::write_close();
    set_time_limit(\block_pdf2submission\service::REQUEST_TIMEOUT);
    \block_pdf2submission\service::analyse($assignment, $token);
    redirect(new moodle_url($url, ['batch'=>$token]));
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_pdf2submission'));
if ($notice) { echo $OUTPUT->notification($notice, 'success'); }
echo html_writer::tag('p', get_string('intro', 'block_pdf2submission'));
echo \block_pdf2submission\assignment_picker::render($course, $id);
if ($assignment) {
    echo $OUTPUT->heading(format_string($assignment->get_instance()->name), 3);
    echo $OUTPUT->single_button(new moodle_url($url, ['action'=>'sheets']), get_string('sheets', 'block_pdf2submission'));
    if ($token) {
        $batch = \block_pdf2submission\service::load($token, $id);
        if ($batch['done']) {
            echo $OUTPUT->notification(get_string('saved', 'block_pdf2submission', count($batch['groups'])), 'success');
        }
        $students = \block_pdf2submission\service::students($assignment);
        if ($batch['errors']) {
            echo $OUTPUT->notification(get_string('unreadable', 'block_pdf2submission', implode(', ', $batch['errors'])), 'error');
        }
        if (!empty($batch['inherited'])) {
            echo $OUTPUT->notification(get_string('inheritedpages', 'block_pdf2submission',
                implode(', ', $batch['inherited'])), 'warning');
        }
        if (!empty($batch['ignored'])) {
            echo $OUTPUT->notification(get_string('teacherignoredpages', 'block_pdf2submission',
                implode(', ', $batch['ignored'])), 'info');
        }
        $table = new html_table();
        $table->head = [get_string('fullname'), get_string('pages', 'block_pdf2submission'), 'PDF'];
        foreach ($batch['groups'] as $userid=>$pages) {
            $name = isset($students[$userid]) ? fullname($students[$userid]) : '?';
            $table->data[] = [s($name), s(implode(', ', $pages)), html_writer::link(new moodle_url($url,
                ['action'=>'preview', 'batch'=>$token, 'userid'=>$userid]), get_string('preview', 'block_pdf2submission'))];
        }
        echo html_writer::table($table);
        if (!$batch['done'] && !$batch['errors'] && $batch['groups']) {
            echo html_writer::tag('p', get_string('confirmnote', 'block_pdf2submission'));
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out_omit_querystring()]);
            foreach (['courseid'=>$courseid, 'id'=>$id, 'action'=>'confirm', 'batch'=>$token, 'sesskey'=>sesskey()] as $key=>$value) {
                echo html_writer::empty_tag('input', ['type'=>'hidden', 'name'=>$key, 'value'=>$value]);
            }
            echo html_writer::tag('label', html_writer::empty_tag('input',
                ['type'=>'checkbox', 'name'=>'overwrite', 'value'=>1]) . ' ' .
                get_string('overwrite', 'block_pdf2submission'));
            echo html_writer::tag('p', get_string('overwritenote', 'block_pdf2submission'));
            echo html_writer::tag('button', get_string('confirm', 'block_pdf2submission'),
                ['type'=>'submit', 'class'=>'btn btn-primary']);
            echo html_writer::end_tag('form');
        }
    }
    echo $OUTPUT->heading(get_string('uploadheading', 'block_pdf2submission'), 3);
    $form->display();
}
echo $OUTPUT->footer();
