<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submission;
defined('MOODLE_INTERNAL') || die();

class service {
    public const MAX_UPLOAD_BYTES = 100 * 1024 * 1024;
    public const PROCESS_TIMEOUT = 900;
    // Analysis invokes scan and merge separately, each with its own deadline.
    public const REQUEST_TIMEOUT = 2 * self::PROCESS_TIMEOUT + 60;

    public static function validate_source(string $file): void {
        $size = is_file($file) ? filesize($file) : false;
        if ($size === false || $size === 0) {
            throw new \moodle_exception('processfailed', 'block_pdf2submission');
        }
        if ($size > self::MAX_UPLOAD_BYTES) {
            throw new \moodle_exception('uploadtoolarge', 'block_pdf2submission');
        }
    }

    public static function assignment(int $cmid): \assign {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        require_login($course, false, $cm);
        $context = \context_module::instance($cmid);
        require_capability('block/pdf2submission:manage', $context);
        require_capability('mod/assign:grade', $context);
        require_capability('mod/assign:editothersubmission', $context);
        $assignment = new \assign($context, $cm, $course);
        if ($assignment->get_instance()->teamsubmission || !$assignment->get_submission_plugin_by_type('file')->is_enabled()) {
            throw new \moodle_exception('unsupported', 'block_pdf2submission');
        }
        return $assignment;
    }
    public static function students(\assign $assignment): array {
        $students = get_enrolled_users($assignment->get_context(), 'mod/assign:submit', 0, 'u.*', 'u.lastname,u.firstname', 0, 0, true);
        // Preserve user-ID keys used for QR decoding and submission lookup.
        uasort($students, static function ($a, $b): int {
            $left = trim($a->idnumber ?? '');
            $right = trim($b->idnumber ?? '');
            if (($left === '') !== ($right === '')) {
                return $left === '' ? 1 : -1;
            }
            return strnatcasecmp($left, $right)
                ?: strnatcasecmp($a->username ?? '', $b->username ?? '')
                ?: ($a->id <=> $b->id);
        });
        return $students;
    }
    public static function code(int $cmid, int $userid): string {
        $secret = get_config('block_pdf2submission', 'secret');
        if (!$secret) { throw new \moodle_exception('notconfigured', 'block_pdf2submission'); }
        $body = "P2S1:$cmid:$userid";
        return $body . ':' . hash_hmac('sha256', $body, $secret);
    }
    public static function decode(string $code, int $cmid): int {
        if (!preg_match('/^P2S1:([1-9][0-9]*):([1-9][0-9]*):[0-9a-f]{64}$/D', $code, $match) ||
                (int)$match[1] !== $cmid || !hash_equals(self::code($cmid, (int)$match[2]), $code)) {
            throw new \moodle_exception('badqr', 'block_pdf2submission');
        }
        return (int)$match[2];
    }
    /** Separate signed marker: never interpreted as a student submission. */
    public static function teacher_code(int $cmid, int $userid): string {
        $secret = get_config('block_pdf2submission', 'secret');
        if (!$secret) { throw new \moodle_exception('notconfigured', 'block_pdf2submission'); }
        $body = "P2ST1:$cmid:$userid";
        return $body . ':' . hash_hmac('sha256', $body, $secret);
    }
    public static function is_teacher_code(string $code, int $cmid): bool {
        return preg_match('/^P2ST1:([1-9][0-9]*):([1-9][0-9]*):[0-9a-f]{64}$/D', $code, $match)
            && (int)$match[1] === $cmid
            && hash_equals(self::teacher_code($cmid, (int)$match[2]), $code);
    }
    public static function sheets(\assign $assignment): \pdf {
        global $CFG, $USER;
        require_once($CFG->libdir . '/pdflib.php');
        $students = self::students($assignment);
        if (!$students || count($students) > 500) { throw new \moodle_exception('studentcount', 'block_pdf2submission'); }
        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('kozgopromedium', '', 11);
        // The current teacher is last, even if also enrolled with submit permission.
        unset($students[$USER->id]);
        $recipients = array_values($students);
        $recipients[] = $USER;
        foreach ($recipients as $index => $student) {
            $isteacher = $index === count($recipients) - 1;
            $pdf->AddPage();
            // Enlarge QR modules while keeping the header bottom at 35 mm.
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.2);
            $pdf->Rect(12, 5, 30, 30);
            $code = $isteacher ? self::teacher_code($assignment->get_course_module()->id, $student->id)
                : self::code($assignment->get_course_module()->id, $student->id);
            $pdf->write2DBarcode($code,
                'QRCODE,H', 12, 5, 30, 30, ['border' => 0, 'padding' => 2], 'N');
            $name = trim(preg_replace('/[\\s\\x{3000}]+/u', ' ', fullname($student)));
            // 11 pt text with 8 pt extra leading (19 pt baseline spacing).
            $pdf->setCellHeightRatio(19 / 11);
            // Font/line-box inset places visible text about 8 pt below the QR frame.
            $pdf->SetXY(48, 5 + 3 * 25.4 / 72);
            // MultiCell defaults to justification, which stretches spaces in names.
            $pdf->MultiCell(150, 7, $assignment->get_course()->fullname . "\n" .
                $assignment->get_instance()->name . "\n" . $name .
                (!empty($student->idnumber) ? ' / ' . $student->idnumber : '') .
                ($isteacher ? ' / ' . get_string('teachercopy', 'block_pdf2submission') : ''), 0, 'L');
        }
        return $pdf;
    }
    public static function directory(string $token): string {
        global $USER;
        if (!preg_match('/^[0-9a-f]{32}$/D', $token)) { throw new \moodle_exception('invalidbatch', 'block_pdf2submission'); }
        return make_temp_directory('pdf2submission/' . $USER->id) . '/' . $token;
    }
    public static function load(string $token, int $cmid): array {
        $path = self::directory($token) . '/manifest.json';
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (!$data || $data['cmid'] !== $cmid || $data['expires'] < time()) {
            throw new \moodle_exception('invalidbatch', 'block_pdf2submission');
        }
        return $data;
    }
    public static function processor(string $mode, string $path): array {
        $command = ['/usr/bin/timeout', '--kill-after=10', (string)self::PROCESS_TIMEOUT, '/usr/bin/python3', __DIR__ . '/../cli/pdf.py', $mode, $path];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $path . '/processor.log', 'a']], $pipes);
        if (!is_resource($process)) { throw new \moodle_exception('processfailed', 'block_pdf2submission'); }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);
        $data = json_decode($output, true);
        if ($status || !$data || isset($data['error'])) {
            // Keep raw diagnostics private; display only known, translated reasons.
            file_put_contents($path . '/processor.log', "\nExit: " . $status . "\n" . $output, FILE_APPEND);
            $known = ['encrypted', 'empty', 'pagesize', 'ghostscriptmissing', 'zbarmissing',
                'pypdfmissing', 'pagetimeout', 'renderfailed', 'qrfailed', 'invalidpdf'];
            $code = $data['errorcode'] ?? '';
            if ($status === 124) {
                $code = 'timeout';
            } else if (!in_array($code, $known, true)) {
                $code = 'environment';
            }
            throw new \moodle_exception('processfailedreason', 'block_pdf2submission', '',
                get_string('process_' . $code, 'block_pdf2submission', (int)($data['page'] ?? 0)));
        }
        return $data;
    }
    public static function analyse(\assign $assignment, string $token): array {
        $path = self::directory($token);
        self::validate_source($path . '/source.pdf');
        $result = self::processor('scan', $path);
        $cmid = (int)$assignment->get_course_module()->id;
        $students = self::students($assignment);
        $mapping = self::group_pages($result['pages'], $cmid, $students);
        $groups = $mapping['groups'];
        $manifest = ['cmid' => $cmid, 'expires' => time() + DAYSECS,
            'groups' => $groups, 'errors' => $mapping['errors'],
            'inherited' => $mapping['inherited'], 'ignored' => $mapping['ignored'] ?? [], 'done' => false];
        file_put_contents($path . '/groups.json', json_encode((object)$groups), LOCK_EX);
        if ($groups) { self::processor('merge', $path); }
        file_put_contents($path . '/manifest.json', json_encode($manifest), LOCK_EX);
        return $manifest;
    }
    /** Assign QR-less continuation pages in original scan order. */
    public static function group_pages(array $pages, int $cmid, array $students): array {
        $groups = [];
        $errors = [];
        $inherited = [];
        $ignored = [];
        $previous = null;
        foreach ($pages as $page) {
            try {
                if (!$page['codes']) {
                    if ($previous === 0) { $ignored[] = $page['page']; continue; }
                    if ($previous === null) { throw new \moodle_exception('badqr', 'block_pdf2submission'); }
                    $userid = $previous;
                    $inherited[] = $page['page'];
                } else {
                    if (count($page['codes']) !== 1) { throw new \moodle_exception('badqr', 'block_pdf2submission'); }
                    if (self::is_teacher_code($page['codes'][0], $cmid)) {
                        $previous = 0;
                        $ignored[] = $page['page'];
                        continue;
                    }
                    $userid = self::decode($page['codes'][0], $cmid);
                    if (!isset($students[$userid])) { throw new \moodle_exception('badqr', 'block_pdf2submission'); }
                    $previous = $userid;
                }
                $groups[$userid][] = $page['page'];
            } catch (\moodle_exception $error) {
                $errors[] = $page['page'];
                $previous = null;
            }
        }
        $result = ['groups' => $groups, 'errors' => $errors, 'inherited' => $inherited];
        if ($ignored) { $result['ignored'] = $ignored; }
        return $result;
    }
    public static function submission_filename(\assign $assignment, \stdClass $student): string {
        $identifier = trim($student->idnumber ?? '');
        if ($identifier === '') { $identifier = trim($student->username ?? ''); }
        if ($identifier === '') { $identifier = (string)$student->id; }
        $title = clean_param($assignment->get_instance()->name, PARAM_FILE);
        $identifier = clean_param($identifier, PARAM_FILE);
        if ($identifier === '') { $identifier = (string)$student->id; }
        return \core_text::substr($title ?: 'assignment', 0, 100) . '_' .
            \core_text::substr($identifier, 0, 80) . '.pdf';
    }
    public static function confirm(\assign $assignment, string $token, bool $overwrite = false): int {
        global $USER, $DB;
        $cmid = (int)$assignment->get_course_module()->id;
        $lock = \core\lock\lock_config::get_lock_factory('block_pdf2submission')->get_lock('assign_' . $cmid, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'block_pdf2submission'); }
        try {
            $batch = self::load($token, $cmid);
            if ($batch['done'] || $batch['errors'] || !$batch['groups']) {
                throw new \moodle_exception('cannotconfirm', 'block_pdf2submission');
            }
            $students = self::students($assignment);
            $plugin = $assignment->get_submission_plugin_by_type('file');
            $path = self::directory($token);
            $modified = [];
            foreach ($batch['groups'] as $userid => $pages) {
                $file = $path . '/user-' . (int)$userid . '.pdf';
                if (!isset($students[$userid]) || !$assignment->can_edit_submission($userid, $USER->id) ||
                        !$assignment->submissions_open($userid, false, null)) {
                    throw new \moodle_exception('noteditable', 'block_pdf2submission', '', fullname($students[$userid] ?? (object)['firstname'=>'?', 'lastname'=>'']));
                }
                $submission = $assignment->get_user_submission($userid, false);
                if ($submission && !$overwrite && !$assignment->submission_empty($submission)) {
                    throw new \moodle_exception('existing', 'block_pdf2submission', '', fullname($students[$userid]));
                }
                $modified[$userid] = $submission ? (int)$submission->timemodified : 0;
                $limit = (int)$plugin->get_config('maxsubmissionsizebytes');
                if (!is_file($file) || ($limit > 0 && filesize($file) > $limit)) {
                    throw new \moodle_exception('filesize', 'block_pdf2submission');
                }
                $accepted = $plugin->get_config('filetypeslist');
                if ($accepted && $accepted !== '*' && !file_extension_in_typegroup('paper.pdf', $accepted)) {
                    throw new \moodle_exception('pdftype', 'block_pdf2submission');
                }
            }
            $drafts = [];
            $context = \context_user::instance($USER->id);
            $fs = get_file_storage();
            $transaction = $DB->start_delegated_transaction();
            try {
                foreach ($batch['groups'] as $userid => $pages) {
                    $draft = file_get_unused_draft_itemid();
                    $drafts[] = $draft;
                    $fs->create_file_from_pathname(['contextid' => $context->id, 'component' => 'user',
                        'filearea' => 'draft', 'itemid' => $draft, 'filepath' => '/', 'filename' => self::submission_filename($assignment, $students[$userid])],
                        $path . '/user-' . (int)$userid . '.pdf');
                    $notices = [];
                    $data = (object)['userid' => (int)$userid, 'files_filemanager' => $draft, 'lastmodified' => $modified[$userid]];
                    if (!$assignment->save_submission($data, $notices)) {
                        throw new \moodle_exception('savefailed', 'block_pdf2submission');
                    }
                    if ($assignment->get_instance()->submissiondrafts && !$assignment->submit_for_grading($data, $notices)) {
                        throw new \moodle_exception('savefailed', 'block_pdf2submission');
                    }
                }
                $transaction->allow_commit();
            } catch (\Throwable $error) { $transaction->rollback($error); }
            finally {
                foreach ($drafts as $draft) { $fs->delete_area_files($context->id, 'user', 'draft', $draft); }
            }
            $batch['done'] = true;
            file_put_contents($path . '/manifest.json', json_encode($batch), LOCK_EX);
            return count($batch['groups']);
        } finally { $lock->release(); }
    }
}
