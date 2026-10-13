<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__.'/../../config.php');
$courseid=required_param('courseid',PARAM_INT); $id=required_param('id',PARAM_INT);
require_login(get_course($courseid)); require_sesskey();
$a=\block_pdf2submissionbeta\service::assignment($id);
if ((int)$a->get_course()->id!==$courseid) { throw new moodle_exception('invalidparameter'); }
\block_pdf2submissionbeta\service::sheets($a)->Output('pdf2submission-qr.pdf','D');
