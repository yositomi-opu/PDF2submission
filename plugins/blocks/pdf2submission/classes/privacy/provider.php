<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submission\privacy;
defined('MOODLE_INTERNAL') || die();
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_subsystem_link('core_files', [], 'privacy:files');
        $collection->add_external_location_link('local_temporary_files', [], 'privacy:temporary');
        return $collection;
    }
    private static function batches(): array {
        global $CFG;
        $result = [];
        foreach (glob($CFG->tempdir . '/pdf2submission/*/*', GLOB_ONLYDIR) ?: [] as $path) {
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
        foreach (self::batches() as $data) {
            if (self::involved($data, $userid)) { $list->add_system_context(); break; }
        }
        return $list;
    }
    public static function get_users_in_context(\core_privacy\local\request\userlist $list) {
        if ($list->get_context()->contextlevel !== CONTEXT_SYSTEM) { return; }
        foreach (self::batches() as $data) { $list->add_users(array_merge([$data['owner']], array_keys($data['groups'] ?? []))); }
    }
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $list) {
        if (!in_array(\context_system::instance()->id, $list->get_contextids())) { return; }
        $userid = $list->get_user()->id;
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
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
        foreach (self::batches() as $path => $data) { remove_dir($path); }
    }
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $list) {
        if (!in_array(\context_system::instance()->id, $list->get_contextids())) { return; }
        foreach (self::batches() as $path => $data) {
            if (self::involved($data, $list->get_user()->id)) { remove_dir($path); }
        }
    }
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $list) {
        if ($list->get_context()->contextlevel !== CONTEXT_SYSTEM) { return; }
        foreach (self::batches() as $path => $data) {
            foreach ($list->get_userids() as $userid) {
                if (self::involved($data, $userid)) { remove_dir($path); break; }
            }
        }
    }
}
