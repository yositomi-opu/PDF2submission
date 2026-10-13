<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submissionbeta;
defined('MOODLE_INTERNAL') || die();
class roster {
    /** CSV ordering includes every active enrolled role, not just submitters. */
    public static function participants(int $courseid): array {
        return get_enrolled_users(\context_course::instance($courseid), '', 0, 'u.*', 'u.id', 0, 0, true);
    }

    /** Listed participants first; append unlisted submitters, never unlisted teachers. */
    public static function sheet_order(array $participants, array $submitters, array $ids, array $teachers): array {
        $result = [];
        foreach ($ids as $id) {
            if (isset($participants[$id])) {
                $result[$id] = ['user'=>$participants[$id], 'teacher'=>isset($teachers[$id]) || !isset($submitters[$id])];
            }
        }
        foreach ($submitters as $id=>$user) {
            if (!isset($result[$id]) && !isset($teachers[$id])) { $result[$id] = ['user'=>$user, 'teacher'=>false]; }
        }
        return $result;
    }
    public static function load(int $courseid): array {
        global $DB;
        $json = $DB->get_field('block_pdf2subbeta_order', 'userids', ['courseid'=>$courseid]);
        return $json ? (json_decode($json, true) ?: []) : [];
    }
    /** UTF-8 CSV, one identifier per row, optional identifier header. */
    public static function parse(string $csv, array $students): array {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        if (!preg_match('//u', $csv)) { throw new \moodle_exception('orderinvalid', 'block_pdf2submissionbeta'); }
        $stream = fopen('php://temp', 'w+'); fwrite($stream, $csv); rewind($stream);
        $field = 'idnumber'; $ids = []; $first = true;
        try {
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                if (count($row) === 1 && trim($row[0] ?? '') === '') { continue; }
                if (count($row) !== 1) { throw new \moodle_exception('orderinvalid', 'block_pdf2submissionbeta'); }
                $value = trim($row[0]);
                if ($first && in_array($value, ['idnumber', 'username', '学籍番号', '学生番号'], true)) {
                    $field = $value === 'username' ? 'username' : 'idnumber'; $first = false; continue;
                }
                $first = false;
                $matches = array_filter($students, static fn($s) => trim((string)($s->$field ?? '')) === $value);
                if (count($matches) !== 1) { throw new \moodle_exception('ordermismatch', 'block_pdf2submissionbeta', '', $value); }
                $student = reset($matches);
                if (in_array((int)$student->id, $ids, true)) { throw new \moodle_exception('orderduplicate', 'block_pdf2submissionbeta', '', $value); }
                $ids[] = (int)$student->id;
            }
        } finally { fclose($stream); }
        if (!$ids) { throw new \moodle_exception('orderinvalid', 'block_pdf2submissionbeta'); }
        return $ids;
    }
    public static function save(int $courseid, array $ids): void {
        global $DB, $USER;
        $lock = \core\lock\lock_config::get_lock_factory('block_pdf2submissionbeta')->get_lock('order_'.$courseid, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'block_pdf2submissionbeta'); }
        try {
            $record = (object)['courseid'=>$courseid, 'ownerid'=>$USER->id, 'userids'=>json_encode($ids), 'timemodified'=>time()];
            $record->id = $DB->get_field('block_pdf2subbeta_order', 'id', ['courseid'=>$courseid]);
            if ($record->id) { $DB->update_record('block_pdf2subbeta_order', $record); }
            else { unset($record->id); $DB->insert_record('block_pdf2subbeta_order', $record); }
        } finally { $lock->release(); }
    }
    public static function apply(array $students, array $ids): array {
        $result = [];
        foreach ($ids as $id) { if (isset($students[$id])) { $result[$id] = $students[$id]; } }
        return $result + $students;
    }
}
