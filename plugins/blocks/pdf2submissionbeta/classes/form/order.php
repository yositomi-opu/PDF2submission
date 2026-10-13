<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submissionbeta\form;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
class order extends \moodleform {
    public function definition() {
        $m = $this->_form;
        $m->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $m->setType('courseid', PARAM_INT);
        $m->addElement('filepicker', 'ordercsv', get_string('ordercsv', 'block_pdf2submissionbeta'), null,
            ['maxbytes'=>1024*1024, 'accepted_types'=>['.csv']]);
        $m->addRule('ordercsv', null, 'required');
        $this->add_action_buttons(false, get_string('saveorder', 'block_pdf2submissionbeta'));
    }
}
