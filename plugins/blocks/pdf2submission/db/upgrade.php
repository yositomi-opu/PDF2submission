<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
function xmldb_block_pdf2submission_upgrade($oldversion) {
    if ($oldversion < 2026100600) {
        if (!get_config('block_pdf2submission', 'secret')) {
            set_config('secret', bin2hex(random_bytes(32)), 'block_pdf2submission');
        }
        upgrade_block_savepoint(true, 2026100600, 'pdf2submission');
    }
    return true;
}
