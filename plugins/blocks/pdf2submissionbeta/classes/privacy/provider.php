<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submissionbeta\privacy;
defined('MOODLE_INTERNAL') || die();
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('block_pdf2subbeta_order',
            ['courseid'=>'privacy:order', 'ownerid'=>'privacy:order', 'userids'=>'privacy:order', 'timemodified'=>'privacy:order'], 'privacy:order');
        $collection->add_subsystem_link('core_files', [], 'privacy:files');
        $collection->add_external_location_link('local_temporary_files', [], 'privacy:temporary');
        return $collection;
    }
    private static function orders(): array {
        global $DB;
        return $DB->get_records('block_pdf2subbeta_order');
    }
    private static function order_users($record): array {
        return array_unique(array_merge([(int)$record->ownerid], json_decode($record->userids,true) ?: []));
    }
    private static function delete_orders(array $ids): void {
        global $DB;
        foreach (self::orders() as $record) {
            if (!array_intersect($ids, self::order_users($record))) { continue; }
            // Serialize with CSV uploads and re-read so erasure cannot restore stale ordering.
            $lock = \core\lock\lock_config::get_lock_factory('block_pdf2submissionbeta')->get_lock('order_'.$record->courseid, 10);
            if (!$lock) { throw new \moodle_exception('busy', 'block_pdf2submissionbeta'); }
            try {
                $current = $DB->get_record('block_pdf2subbeta_order', ['id'=>$record->id]);
                if (!$current) { continue; }
                $remaining = array_values(array_diff(json_decode($current->userids, true) ?: [], $ids));
                $current->userids = json_encode($remaining);
                if (in_array((int)$current->ownerid, $ids)) { $current->ownerid = 0; }
                $current->timemodified = time();
                $DB->update_record('block_pdf2subbeta_order', $current);
            } finally { $lock->release(); }
        }
    }
    private static function batches(): array {
        global $CFG;
        $result = [];
        foreach (glob($CFG->tempdir . '/pdf2submissionbeta/*/*', GLOB_ONLYDIR) ?: [] as $path) {
            $data = is_file($path . '/manifest.json') ? json_decode(file_get_contents($path . '/manifest.json'), true) : [];
            $data = $data ?: [];
            $data['owner'] = (int)basename(dirname($path));
            $result[$path] = $data;
        }
        return $result;
    }
    private static function involved(array $data, int $userid): bool {
        return $data['owner'] === $userid || isset($data['groups'][$userid]);
    }
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $list = new \core_privacy\local\request\contextlist();
        foreach (self::orders() as $record) {
            if (in_array($userid,self::order_users($record))) { $list->add_system_context(); break; }
        }
        foreach (self::batches() as $data) {
            if (self::involved($data, $userid)) { $list->add_system_context(); break; }
        }
        return $list;
    }
    public static function get_users_in_context(\core_privacy\local\request\userlist $list) {
        if ($list->get_context()->contextlevel !== CONTEXT_SYSTEM) { return; }
        foreach (self::orders() as $record) { $list->add_users(self::order_users($record)); }
        foreach (self::batches() as $data) { $list->add_users(array_merge([$data['owner']], array_keys($data['groups'] ?? []))); }
    }
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $list) {
        if (!in_array(\context_system::instance()->id, $list->get_contextids())) { return; }
        $userid = $list->get_user()->id;
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        foreach (self::orders() as $record) {
            $ids=json_decode($record->userids,true) ?: [];
            if (in_array($userid,self::order_users($record))) {
                // Export only this user's membership, not other students' identities.
                $writer->export_data(['PDF2submission Beta order', (string)$record->courseid],
                    (object)['courseid'=>$record->courseid,'savedbyyou'=>(int)$record->ownerid===$userid,
                    'position'=>in_array($userid,$ids) ? array_search($userid,$ids)+1 : null]);
            }
        }
        foreach (self::batches() as $path => $data) {
            if (!self::involved($data, $userid)) { continue; }
            $file = $data['owner'] === $userid ? 'source.pdf' : 'user-' . $userid . '.pdf';
            if (is_file($path . '/' . $file)) {
                $writer->export_custom_file(['PDF2submission', basename($path)], 'paper.pdf', file_get_contents($path . '/' . $file));
            }
        }
    }
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel !== CONTEXT_SYSTEM) { return; }
        global $DB; $DB->delete_records('block_pdf2subbeta_order');
        foreach (self::batches() as $path => $data) { remove_dir($path); }
    }
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $list) {
        if (!in_array(\context_system::instance()->id, $list->get_contextids())) { return; }
        self::delete_orders([(int)$list->get_user()->id]);
        foreach (self::batches() as $path => $data) {
            if (self::involved($data, $list->get_user()->id)) { remove_dir($path); }
        }
    }
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $list) {
        if ($list->get_context()->contextlevel !== CONTEXT_SYSTEM) { return; }
        self::delete_orders($list->get_userids());
        foreach (self::batches() as $path => $data) {
            foreach ($list->get_userids() as $userid) {
                if (self::involved($data, $userid)) { remove_dir($path); break; }
            }
        }
    }
}
