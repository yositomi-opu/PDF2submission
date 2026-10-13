<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Test page order and signed identities using a PDF recorder; no Moodle data.
define('MOODLE_INTERNAL', true);
class assign {
    public function get_context() { return null; }
    public function get_course_module() { return (object)['id'=>7]; }
    public function get_course() { return (object)['fullname'=>'Test course']; }
    public function get_instance() { return (object)['name'=>'Test assignment']; }
}
class pdf {
    public array $codes = [];
    public array $headers = [];
    public int $pages = 0;
    public function __construct(...$args) {}
    public function AddPage() { $this->pages++; }
    public function write2DBarcode($code, ...$args) { $this->codes[]=$code; }
    public function MultiCell($w,$h,$text,...$args) { $this->headers[]=$text; }
    public function __call($name,$args) {}
}
function get_config(...$args) { return 'test-only-key'; }
function get_enrolled_users(...$args) { return $GLOBALS['fixture']; }
function fullname($user) { return $user->firstname . ' ' . $user->lastname; }
function get_string(...$args) { return 'Teacher copy'; }
require __DIR__ . '/../../plugins/blocks/pdf2submission/classes/service.php';
$libdir = sys_get_temp_dir() . '/pdf2-stub-' . bin2hex(random_bytes(8));
mkdir($libdir, 0700);
file_put_contents($libdir . '/pdflib.php', '<?php // PDF recorder is defined above.');
$CFG=(object)['libdir'=>$libdir];
$USER=(object)['id'=>90,'firstname'=>'Test','lastname'=>'Teacher','username'=>'teacher','idnumber'=>'T1'];
$fixture=[];
foreach ([[2,'A10'],[1,'A2']] as [$id,$number]) {
    $fixture[$id]=(object)['id'=>$id,'idnumber'=>$number,'username'=>'s'.$id,'firstname'=>'Test','lastname'=>'Student'.$id];
}
try {
    foreach ([false,true] as $dualenrolled) {
        if ($dualenrolled) { $fixture[90]=$USER; }
        $pdf=\block_pdf2submission\service::sheets(new assign());
        $expected=[\block_pdf2submission\service::code(7,1),\block_pdf2submission\service::code(7,2),
            \block_pdf2submission\service::teacher_code(7,90)];
        if ($pdf->pages !== 3 || $pdf->codes !== $expected ||
            !str_contains($pdf->headers[2], 'Test Teacher / T1 / Teacher copy')) {
            throw new Exception('Wrong page order, teacher count, identity or label');
        }
    }
    echo "PASS: sorted student pages, current teacher last exactly once, distinct signed QR and teacher header\n";
} finally {
    unlink($libdir . '/pdflib.php');
    rmdir($libdir);
}
