<?php
// SPDX-License-Identifier: GPL-3.0-or-later
define('MOODLE_INTERNAL', true);
class assign { public function get_context() { return null; } public function get_course() { return (object)["id"=>42]; } }
function get_enrolled_users(...$args) { return $GLOBALS['fixture']; }
require __DIR__ . '/../../plugins/blocks/pdf2submissionbeta/classes/service.php';
require __DIR__ . '/../../plugins/blocks/pdf2submissionbeta/classes/roster.php';
$DB = new class { public function get_field(...$args) { return false; } };
$fixture = [];
foreach ([[80,'','empty'],[11,'A10','z'],[25,'A2','z'],[30,' A1 ','z'],
          [44,'A2','a'],[45,'A2','a'],[81,'  ','empty']] as [$id,$number,$username]) {
    $fixture[$id]=(object)['id'=>$id,'idnumber'=>$number,'username'=>$username];
}
$ordered=\block_pdf2submissionbeta\service::students(new assign());
if (array_keys($ordered) !== [30,44,45,25,11,80,81]) { throw new Exception('Wrong order or lost user IDs'); }
foreach ($ordered as $id=>$student) {
    if ($id !== $student->id || $student !== $fixture[$id]) { throw new Exception('Changed identity'); }
}
$fixture=[];
if (\block_pdf2submissionbeta\service::students(new assign()) !== []) { throw new Exception('Empty roster'); }
echo "Student natural order, missing IDs, tie-breaks and key preservation: PASS\n";
