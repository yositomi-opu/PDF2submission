<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace core_privacy\local\metadata { interface provider {} }
namespace core_privacy\local\request\plugin { interface provider {} }
namespace core_privacy\local\request { interface core_userlist_provider {} }
namespace core\lock {
    class lock_config {
        public static function get_lock_factory($type) { return new self(); }
        public function get_lock($key, $timeout) { return new class { public function release() {} }; }
    }
}
namespace {
    define('MOODLE_INTERNAL', true);
    require __DIR__.'/../../plugins/blocks/pdf2submissionbeta/classes/privacy/provider.php';
    $DB = new class {
        public array $rows = [];
        public function get_records($table) { return $this->rows; }
        public function get_record($table, $where) { return clone $this->rows[$where['id']]; }
        public function update_record($table, $row) { $this->rows[$row->id] = $row; }
    };
    $DB->rows = [1=>(object)['id'=>1,'courseid'=>42,'ownerid'=>90,'userids'=>'[3,1,2]'],
                 2=>(object)['id'=>2,'courseid'=>43,'ownerid'=>91,'userids'=>'[5,4]']];
    $method = new ReflectionMethod(\block_pdf2submissionbeta\privacy\provider::class, 'delete_orders');
    $method->invoke(null, [1]);
    if ($DB->rows[1]->userids !== '[3,2]' || $DB->rows[2]->userids !== '[5,4]') {
        throw new Exception('Erasing one student must preserve others and other courses');
    }
    $method->invoke(null, [90]);
    if ($DB->rows[1]->ownerid !== 0 || $DB->rows[1]->userids !== '[3,2]') {
        throw new Exception('Erasing uploader must preserve course ordering');
    }
    $method->invoke(null, [3,2]);
    if ($DB->rows[1]->userids !== '[]') { throw new Exception('Erased IDs remain'); }
    echo "Course order survives student/uploader erasure: PASS\n";
}
