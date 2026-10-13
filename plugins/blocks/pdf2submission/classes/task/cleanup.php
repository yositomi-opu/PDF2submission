<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submission\task;
defined('MOODLE_INTERNAL') || die();
class cleanup extends \core\task\scheduled_task {
    public function get_name() { return get_string('cleanup', 'block_pdf2submission'); }
    public function execute() {
        global $CFG;
        foreach (glob($CFG->tempdir . '/pdf2submission/*/*', GLOB_ONLYDIR) ?: [] as $path) {
            if (filemtime($path) < time() - DAYSECS) { remove_dir($path); }
        }
    }
}
