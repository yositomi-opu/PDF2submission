<?php
// SPDX-License-Identifier: GPL-3.0-or-later
if (getenv('MACMOODLE_PDF2_TEST') !== '1') {fwrite(STDERR, "Only run in a disposable test Moodle with MACMOODLE_PDF2_TEST=1.\n");exit(1);}
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$CFG->debug = DEBUG_DEVELOPER; $CFG->debugdisplay = 1;
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/user/lib.php';
require_once $CFG->dirroot . '/lib/enrollib.php';
\core\session\manager::set_user(get_admin());
function check($condition, $message) { if (!$condition) { throw new Exception($message); } echo "PASS $message\n"; }
$course = create_course((object)['fullname'=>'PDF2submission verification', 'shortname'=>'P2S-'.time(), 'category'=>1]);
$manual = enrol_get_plugin('manual');
$instance = $DB->get_record('enrol', ['courseid'=>$course->id, 'enrol'=>'manual']);
$role = $DB->get_record('role', ['shortname'=>'student']);
$students = [];
foreach (['alpha', 'beta'] as $name) {
    $user = (object)['username'=>'p2stest-'.$name.'-'.time(), 'password'=>'Test1!'.hash('sha256', random_bytes(16)),
      'firstname'=>ucfirst($name), 'lastname'=>'Sample', 'email'=>$name.'@example.invalid', 'confirmed'=>1,
      'auth'=>'manual', 'mnethostid'=>$CFG->mnet_localhost_id];
    $user->id=user_create_user($user);
    $students[$user->id]=$user;
    $manual->enrol_user($instance, $user->id, $role->id);
}
$mod = (object)['modulename'=>'assign', 'module'=>$DB->get_field('modules','id',['name'=>'assign']),
    'course'=>$course->id, 'section'=>0, 'visible'=>1, 'name'=>'Paper test',
    'introeditor'=>['text'=>'Integration verification','format'=>FORMAT_HTML,'itemid'=>0],
    'grade'=>100, 'submissiondrafts'=>1, 'requiresubmissionstatement'=>0, 'teamsubmission'=>0,
    'assignsubmission_file_enabled'=>1, 'assignsubmission_file_maxfiles'=>1,
    'assignsubmission_file_maxsizebytes'=>20971520, 'assignsubmission_file_filetypeslist'=>'.pdf',
    'assignsubmission_onlinetext_enabled'=>0, 'assignfeedback_comments_enabled'=>1];
foreach (['sendnotifications','sendlatenotifications','sendstudentnotifications','duedate','cutoffdate','gradingduedate','allowsubmissionsfromdate','requireallteammemberssubmit','blindmarking','markingworkflow'] as $key) { $mod->$key=0; }
$created = add_moduleinfo($mod, $course);
$teacher = (object)['username'=>'p2stest-teacher-'.time(), 'password'=>'Test1!'.bin2hex(random_bytes(16)),
    'firstname'=>'Teacher', 'lastname'=>'Sample', 'email'=>'teacher@example.invalid', 'confirmed'=>1,
    'auth'=>'manual', 'mnethostid'=>$CFG->mnet_localhost_id];
$teacher->id=user_create_user($teacher);
$manual->enrol_user($instance, $teacher->id, $DB->get_field('role','id',['shortname'=>'editingteacher']));
assign_capability('mod/assign:editothersubmission', CAP_ALLOW, $DB->get_field('role','id',['shortname'=>'editingteacher']), context_course::instance($course->id)->id);
\core\session\manager::set_user($DB->get_record('user',['id'=>$teacher->id]));
$a = \block_pdf2submissionbeta\service::assignment($created->coursemodule);
check(count(\block_pdf2submissionbeta\service::students($a)) === 2, 'enrolled students');
$token=bin2hex(random_bytes(16));
$dir=\block_pdf2submissionbeta\service::directory($token);mkdir($dir,0700);
$sheets=\block_pdf2submissionbeta\service::sheets($a);
$sheets->AddPage();
$sheets->write2DBarcode(\block_pdf2submissionbeta\service::code($created->coursemodule, array_key_first($students)), 'QRCODE,H',12,10,36,36,['padding'=>2],'N');
$sheets->Output($dir.'/source.pdf','F');
if (getenv('PDF2_UI_FIXTURE')) {echo json_encode(['courseid'=>$course->id,'cmid'=>$created->coursemodule,'file'=>$dir.'/source.pdf'])."\n";exit;}
$batch=\block_pdf2submissionbeta\service::analyse($a,$token);
check(count($batch['groups'])===2 && array_sum(array_map('count',$batch['groups']))===3 && !$batch['errors'], 'QR sheets rasterized, decoded and split');
foreach ($students as $user) {
 check(\block_pdf2submissionbeta\service::decode(\block_pdf2submissionbeta\service::code($created->coursemodule,$user->id),$created->coursemodule)===$user->id,'signed QR roundtrip');
}
try { \block_pdf2submissionbeta\service::decode('123,456,oldcourse,olduser',$created->coursemodule);throw new Exception('accepted legacy QR'); }
catch (moodle_exception $e) { check($e->errorcode==='badqr','legacy QR rejected'); }
check(count(\block_pdf2submissionbeta\privacy\provider::get_contexts_for_userid(array_key_first($students))->get_contextids())===1,'privacy context discovered');
try {\block_pdf2submissionbeta\service::decode(\block_pdf2submissionbeta\service::code($created->coursemodule, array_key_first($students)), $created->coursemodule+1);throw new Exception('wrong assignment accepted');}
catch (moodle_exception $e) {check($e->errorcode==='badqr','wrong assignment rejected');}
$blank=bin2hex(random_bytes(16));$blankpath=\block_pdf2submissionbeta\service::directory($blank);mkdir($blankpath,0700);
$blankpdf=new pdf();$blankpdf->AddPage();$blankpdf->Text(20,20,'No QR');$blankpdf->Output($blankpath.'/source.pdf','F');
$bad=\block_pdf2submissionbeta\service::analyse($a,$blank);
check(count($bad['errors'])===1 && !$bad['groups'],'unreadable page blocks registration');
try {\block_pdf2submissionbeta\service::confirm($a,$blank);throw new Exception('bad page accepted');}
catch (moodle_exception $e) {check($e->errorcode==='cannotconfirm','unreadable batch cannot commit');}
check(\block_pdf2submissionbeta\service::confirm($a,$token)===2,'Moodle proxy submission API');
foreach ($students as $user) {
 $s=$a->get_user_submission($user->id,false);
 check($s->status==='submitted','submission is finalized');
 check(count(get_file_storage()->get_area_files($a->get_context()->id,'assignsubmission_file','submission_files',$s->id,'id',false))===1,'one PDF stored by Moodle');
}
try { \block_pdf2submissionbeta\service::confirm($a,$token);throw new Exception('duplicate accepted'); }
catch (moodle_exception $e) {check($e->errorcode==='cannotconfirm','duplicate batch blocked');}
$second=bin2hex(random_bytes(16));$secondpath=\block_pdf2submissionbeta\service::directory($second);mkdir($secondpath,0700);copy($dir.'/source.pdf',$secondpath.'/source.pdf');
\block_pdf2submissionbeta\service::analyse($a,$second);
try {\block_pdf2submissionbeta\service::confirm($a,$second);throw new Exception('overwrite accepted');}
catch (moodle_exception $e) {check($e->errorcode==='existing','existing submissions preserved');}
check(\block_pdf2submissionbeta\service::confirm($a,$second,true)===2,'explicit overwrite of finalized submissions');
foreach ($students as $user) {
 $submission=$a->get_user_submission($user->id,false);
 $files=get_file_storage()->get_area_files($a->get_context()->id,'assignsubmission_file','submission_files',$submission->id,'id',false);
 check(count($files)===1,'overwrite replaces rather than appends');
 check(reset($files)->get_filename()===\block_pdf2submissionbeta\service::submission_filename($a,$user),'named submission PDF');
 get_file_storage()->delete_area_files($a->get_context()->id,'assignsubmission_file','submission_files',$submission->id);
 check($a->submission_empty($submission),'empty submission record retained');
}
$third=bin2hex(random_bytes(16));$thirdpath=\block_pdf2submissionbeta\service::directory($third);mkdir($thirdpath,0700);
copy($dir.'/source.pdf',$thirdpath.'/source.pdf');
\block_pdf2submissionbeta\service::analyse($a,$third);
check(\block_pdf2submissionbeta\service::confirm($a,$third)===2,'empty retained submission can be registered without overwrite');
$student=reset($students);
\core\session\manager::set_user($DB->get_record('user',['id'=>$student->id]));
try {\block_pdf2submissionbeta\service::assignment($created->coursemodule);throw new Exception('student access accepted');}
catch (required_capability_exception $e) {echo "PASS student cannot import papers\n";}
echo "INTEGRATION SUCCESS\n";
