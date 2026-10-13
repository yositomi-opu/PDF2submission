<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__.'/../../config.php');
$courseid=required_param('courseid',PARAM_INT); $course=get_course($courseid);
require_login($course); require_capability('block/pdf2submissionbeta:manage',context_course::instance($courseid));
$url=new moodle_url('/blocks/pdf2submissionbeta/scan.php',['courseid'=>$courseid]);
$PAGE->set_url($url); $PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('pluginname','block_pdf2submissionbeta')); $PAGE->set_heading(format_string($course->fullname));
$form=new \block_pdf2submissionbeta\form\upload($url,['courseid'=>$courseid,'id'=>0]);
if ($form->get_data()) {
    $token=bin2hex(random_bytes(16)); $dir=\block_pdf2submissionbeta\service::directory($token); mkdir($dir,0700);
    if (!$form->save_file('scan',$dir.'/source.pdf')) { throw new moodle_exception('processfailed','block_pdf2submissionbeta'); }
    \core\session\manager::write_close(); set_time_limit(\block_pdf2submissionbeta\service::REQUEST_TIMEOUT);
    try { \block_pdf2submissionbeta\batch::analyse($course,$token); }
    catch (\Throwable $e) { remove_dir($dir); throw $e; }
    redirect(new moodle_url('/blocks/pdf2submissionbeta/index.php',['courseid'=>$courseid,'scan'=>$token]));
}
echo $OUTPUT->header(); $form->display(); echo $OUTPUT->footer();
