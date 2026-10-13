<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submission;
defined('MOODLE_INTERNAL') || die();

/** Shared course assignment selector for the block and import page. */
class assignment_picker {
    public static function render(\stdClass $course, int $selected = 0): string {
        global $OUTPUT;
        $options = [];
        foreach (get_all_instances_in_course('assign', $course) as $item) {
            $context = \context_module::instance($item->coursemodule);
            if (!has_capability('mod/assign:grade', $context)) {
                continue;
            }
            $options[$item->coursemodule] = format_string($item->name, true, ['context' => $context]);
        }
        if (!$options) {
            return \html_writer::tag('p', get_string('noassignments', 'block_pdf2submission'));
        }
        $url = new \moodle_url('/blocks/pdf2submission/index.php', ['courseid' => $course->id]);
        $picker = new \single_select($url, 'id', $options, $selected,
            ['' => get_string('chooseassignment', 'block_pdf2submission')]);
        $picker->set_label(get_string('assignment', 'block_pdf2submission'));
        $picker->class = 'pdf2submission-assignment-picker';
        return $OUTPUT->render($picker);
    }
}
