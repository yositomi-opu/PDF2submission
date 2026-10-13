<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submissionbeta;
defined('MOODLE_INTERNAL') || die();
class observer {
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;
        $DB->delete_records('block_pdf2subbeta_order',['courseid'=>$event->objectid]);
    }
}
