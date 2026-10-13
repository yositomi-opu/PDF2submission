<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
class block_pdf2submission extends block_base {
    public function init() { $this->title = get_string('pluginname', 'block_pdf2submission'); }
    public function applicable_formats() { return ['course-view' => true, 'site-index' => false, 'my' => false]; }
    public function get_content() {
        if ($this->content !== null) { return $this->content; }
        $this->content = (object)['text' => '', 'footer' => ''];
        $context = context_course::instance($this->page->course->id);
        if (has_capability('block/pdf2submission:manage', $context)) {
            $this->content->text = \block_pdf2submission\assignment_picker::render($this->page->course);
            $this->content->text .= html_writer::tag('p', get_string('pickernote', 'block_pdf2submission'));
        }
        return $this->content;
    }
}
