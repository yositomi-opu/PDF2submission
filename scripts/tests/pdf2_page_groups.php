<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Standalone regression test; no Moodle database or user data is used.
namespace {
    define('MOODLE_INTERNAL', true);
    class moodle_exception extends \Exception {
        public function __construct($message, ...$args) { parent::__construct($message); }
    }
}
namespace block_pdf2submission {
    function get_config(...$args) { return 'test-only-key'; }
    require $argv[1] ?? __DIR__ . '/../../plugins/blocks/pdf2submission/classes/service.php';
    function check($actual, $expected) {
        if ($actual !== $expected) { throw new \RuntimeException('Unexpected mapping: ' . json_encode($actual)); }
    }
    $a = service::code(7, 1);
    $b = service::code(7, 2);
    $students = [1 => true, 2 => true];
    $map = function($codes) use ($students) {
        $pages = [];
        foreach ($codes as $i => $values) { $pages[] = ['page' => $i + 1, 'codes' => $values]; }
        return service::group_pages($pages, 7, $students);
    };
    check($map([[$a], [], [], [$b], []]),
        ['groups'=>[1=>[1,2,3],2=>[4,5]], 'errors'=>[], 'inherited'=>[2,3,5]]);
    check($map([[], [$a], []]), ['groups'=>[1=>[2,3]], 'errors'=>[1], 'inherited'=>[3]]);
    check($map([[], []]), ['groups'=>[], 'errors'=>[1,2], 'inherited'=>[]]);
    foreach ([['bad-code'], [$a,$b], [service::code(8,1)], [service::code(7,3)]] as $invalid) {
        check($map([[$a], $invalid, [], [$b], []]),
            ['groups'=>[1=>[1],2=>[4,5]], 'errors'=>[2,3], 'inherited'=>[5]]);
    }
    check($map([[$a], [$b], [$a], []]),
        ['groups'=>[1=>[1,3,4],2=>[2]], 'errors'=>[], 'inherited'=>[4]]);
    $teacher = service::teacher_code(7, 90);
    check($map([[$a], [], [$teacher], [], [$b], []]),
        ['groups'=>[1=>[1,2],2=>[5,6]], 'errors'=>[], 'inherited'=>[2,6], 'ignored'=>[3,4]]);
    check($map([[$teacher], []]),
        ['groups'=>[], 'errors'=>[], 'inherited'=>[], 'ignored'=>[1,2]]);
    foreach ([service::teacher_code(8,90), substr($teacher,0,-1) . ($teacher[-1] === '0' ? '1' : '0')] as $badteacher) {
        check($map([[$a], [$badteacher], []]),
            ['groups'=>[1=>[1]], 'errors'=>[2,3], 'inherited'=>[]]);
    }
    check($map([[$teacher], [$a,$teacher], []]),
        ['groups'=>[], 'errors'=>[2,3], 'inherited'=>[], 'ignored'=>[1]]);
    // A distinct teacher marker is excluded even if its user also appears in the roster.
    check($map([[service::teacher_code(7,1)], [$b]]),
        ['groups'=>[2=>[2]], 'errors'=>[], 'inherited'=>[], 'ignored'=>[1]]);
    echo "PASS: signed teacher exclusion, continuations, resume, wrong assignment, tampering, multiple QR\n";
    echo "PASS: continuation, leading blank, all blank, invalid/multiple/wrong QR, repeated student\n";
}
