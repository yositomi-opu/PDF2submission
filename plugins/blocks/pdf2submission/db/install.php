<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
function xmldb_block_pdf2submission_install() {
    set_config('secret', bin2hex(random_bytes(32)), 'block_pdf2submission');
}
