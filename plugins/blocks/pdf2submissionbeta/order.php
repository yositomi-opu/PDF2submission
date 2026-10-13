<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__.'/../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
require_capability('block/pdf2submissionbeta:manage', context_course::instance($courseid));
$url = new moodle_url('/blocks/pdf2submissionbeta/order.php', ['courseid'=>$courseid]);
$PAGE->set_url($url); $PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('ordercsv','block_pdf2submissionbeta')); $PAGE->set_heading(format_string($course->fullname));
if (optional_param('reset', 0, PARAM_BOOL) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    \block_pdf2submissionbeta\roster::save($courseid, []);
    redirect(new moodle_url('/course/view.php', ['id'=>$courseid]));
}
$form = new \block_pdf2submissionbeta\form\order($url, ['courseid'=>$courseid]);
if ($form->get_data()) {
    $csv = $form->get_file_content('ordercsv');
    if ($csv === false || strlen($csv)>1024*1024) { throw new moodle_exception('orderinvalid','block_pdf2submissionbeta'); }
    $students = \block_pdf2submissionbeta\roster::participants($courseid);
    $ids = \block_pdf2submissionbeta\roster::parse($csv, $students);
    \block_pdf2submissionbeta\roster::save($courseid, $ids);
    redirect(new moodle_url('/course/view.php', ['id'=>$courseid]), get_string('ordersaved','block_pdf2submissionbeta',count($ids)));
}
echo $OUTPUT->header(); echo html_writer::tag('p',get_string('orderhelp','block_pdf2submissionbeta'));
$form->display(); echo $OUTPUT->footer();
