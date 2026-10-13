<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'block/pdf2submission:addinstance' => ['riskbitmask' => RISK_XSS, 'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK, 'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
        'clonepermissionsfrom' => 'moodle/site:manageblocks'],
    'block/pdf2submission:manage' => ['riskbitmask' => RISK_PERSONAL, 'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE, 'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],
];
