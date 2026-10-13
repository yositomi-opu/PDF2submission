<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
class block_pdf2submissionbeta extends block_base {
    public function init() { $this->title = get_string('pluginname', 'block_pdf2submissionbeta'); }
    public function applicable_formats() { return ['course-view' => true, 'site-index' => false, 'my' => false]; }
    public function get_content() {
        if ($this->content !== null) { return $this->content; }
        $this->content = (object)['text' => '', 'footer' => ''];
        $context = context_course::instance($this->page->course->id);
        if (has_capability('block/pdf2submissionbeta:manage', $context)) {
            $this->content->text = \block_pdf2submissionbeta\assignment_picker::render($this->page->course);
            $this->content->text .= html_writer::tag('p', get_string('pickernote', 'block_pdf2submissionbeta'));
            $courseid = $this->page->course->id;
            $order = new \block_pdf2submissionbeta\form\order(new moodle_url('/blocks/pdf2submissionbeta/order.php', ['courseid'=>$courseid]), ['courseid'=>$courseid]);
            $count = count(\block_pdf2submissionbeta\roster::load($courseid));
            $details = html_writer::tag('summary', get_string('orderstatus','block_pdf2submissionbeta',$count));
            $details .= html_writer::tag('p', get_string('orderhelp','block_pdf2submissionbeta')).$order->render();
            $details .= $GLOBALS['OUTPUT']->single_button(new moodle_url('/blocks/pdf2submissionbeta/order.php',
                ['courseid'=>$courseid,'reset'=>1]), get_string('resetorder','block_pdf2submissionbeta'), 'post');
            $this->content->text .= html_writer::tag('details', $details);
            $upload = new \block_pdf2submissionbeta\form\upload(new moodle_url('/blocks/pdf2submissionbeta/scan.php', ['courseid'=>$courseid]), ['courseid'=>$courseid,'id'=>0]);
            $this->content->text .= html_writer::tag('h5', get_string('uploadheading','block_pdf2submissionbeta')).$upload->render();
        }
        return $this->content;
    }
}
