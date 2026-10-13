<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
$observers = [['eventname'=>'\\core\\event\\course_deleted',
    'callback'=>'\\block_pdf2submissionbeta\\observer::course_deleted']];
