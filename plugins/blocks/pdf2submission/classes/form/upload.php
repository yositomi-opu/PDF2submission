<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submission\form;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
class upload extends \moodleform {
    public function definition() {
        $m = $this->_form;
        $m->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $m->setType('courseid', PARAM_INT);
        $m->addElement('hidden', 'id', $this->_customdata['id']);
        $m->setType('id', PARAM_INT);
        $m->addElement('filepicker', 'scan', get_string('scan', 'block_pdf2submission'), null,
            ['maxbytes' => \block_pdf2submission\service::MAX_UPLOAD_BYTES, 'accepted_types' => ['.pdf']]);
        $m->addRule('scan', null, 'required');
        $this->add_action_buttons(false, get_string('analyse', 'block_pdf2submission'));
    }
}
