<?php
// SPDX-License-Identifier: GPL-3.0-or-later
define('MOODLE_INTERNAL', true);
class moodle_exception extends Exception { public function __construct($code,$component='', $link='', $a=null) {parent::__construct($code);} }
function get_config($component,$key) { return $component==='block_pdf2submissionbeta' ? 'beta-secret' : 'stable-secret'; }
require __DIR__.'/../../plugins/blocks/pdf2submissionbeta/classes/service.php';
require __DIR__.'/../../plugins/blocks/pdf2submissionbeta/classes/roster.php';
require __DIR__.'/../../plugins/blocks/pdf2submissionbeta/classes/batch.php';
use block_pdf2submissionbeta\service;
use block_pdf2submissionbeta\roster;
use block_pdf2submissionbeta\batch;
function check($ok,$message) { if(!$ok) throw new Exception($message); echo "PASS $message\n"; }
function rejected($fn,$code) {try{$fn();}catch(moodle_exception $e){check($e->getMessage()===$code,$code);return;}throw new Exception('Expected '.$code);}
$students=[1=>(object)['id'=>1,'idnumber'=>'001','username'=>'alpha'],2=>(object)['id'=>2,'idnumber'=>'002','username'=>'beta'],3=>(object)['id'=>3,'idnumber'=>'010','username'=>'gamma']];
check(roster::parse("\xEF\xBB\xBFidnumber\r\n002\r\n001\r\n",$students)===[2,1],'BOM, CRLF and leading zeros preserved');
check(roster::parse("username\nbeta\nalpha\n",$students)===[2,1],'username header');
check(roster::parse("学生番号\n002\n",$students)===[2],'Japanese header');
check(array_keys(roster::apply($students,[2,999,1]))===[2,1,3],'saved order, missing enrollment, unlisted append, key preservation');
rejected(fn()=>roster::parse("001\n001\n",$students),'orderduplicate');
rejected(fn()=>roster::parse("999\n",$students),'ordermismatch');
rejected(fn()=>roster::parse("idnumber\n",$students),'orderinvalid');
rejected(fn()=>roster::parse("001,002\n",$students),'orderinvalid');
$duplicate=$students; $duplicate[4]=(object)['id'=>4,'idnumber'=>'001'];
rejected(fn()=>roster::parse("001\n",$duplicate),'ordermismatch');
function page($n,$code=null) {return ['page'=>$n,'codes'=>$code===null?[]:[$code]];}
$pages=[page(1,service::code(10,1)),page(2),page(3,service::code(20,1)),page(4),page(5,service::teacher_code(20,99)),page(6),page(7,service::code(10,2))];
$res=batch::partition($pages,[10=>$students,20=>$students]);
check(!$res['errors'] && $res['items'][10]['groups']===[1=>[1,2],2=>[7]] && $res['items'][20]['groups']===[1=>[3,4]],'same student in two assignments remains separate');
check($res['items'][20]['ignored']===[5,6],'teacher and following continuation excluded');
check($res['items'][10]['inherited']===[2],'continuation warning retained');
$bad=batch::partition([page(1),page(2,'invalid'),page(3),page(4,service::code(10,1)),page(5)], [10=>$students]);
check($bad['errors']===[1,2,3] && $bad['items'][10]['groups']===[1=>[4,5]],'invalid page clears inheritance; subsequent valid QR resumes');
check(batch::partition([page(1,service::code(30,1))],[10=>$students])['errors']===[1],'unknown assignment rejected');
check(batch::partition([page(1,service::code(10,99))],[10=>$students])['errors']===[1],'unenrolled student rejected');
$body='P2S1:10:1';$code=$body.':'.hash_hmac('sha256',$body,'stable-secret');
check(service::decode($code,10)===1,'stable plugin signature accepted');
rejected(fn()=>service::decode($code,20),'badqr');
$code[10]=$code[10]==='a'?'b':'a';check(!service::valid_signature($code),'tampered signature rejected');
$many=[];$roster=[];
for($i=1;$i<=90;$i++){ $roster[$i]=(object)['id'=>$i];$many[]=page(2*$i-1,service::code(10,$i));$many[]=page(2*$i); }
$many[]=page(181,service::teacher_code(10,100));
$r=batch::partition($many,[10=>$roster]);
check(count($r['items'][10]['groups'])===90 && !$r['errors'] && count($r['items'][10]['inherited'])===90,'90 students / 181 decoded pages grouped (not a raster benchmark)');
// Course lookup must not filter out teachers without submission capability.
class context_course { public static function instance($id) {return (object)['id'=>$id];} }
function get_enrolled_users($context,$cap,$group,$fields,$sort,$from,$limit,$active) {
    check($cap==='' && $active===true && $context->id===42,'all active course roles requested');
    return $GLOBALS['participants'];
}
$teacher=(object)['id'=>90,'idnumber'=>'T090','username'=>'teacher'];
$participants=$students+[90=>$teacher];
check(roster::parse("idnumber\nT090\n001\n",roster::participants(42))===[90,1],'teacher without student role accepted');
$ordered=roster::sheet_order($participants,$students,[2,90,1],[90=>true]);
check(array_keys($ordered)===[2,90,1,3] && $ordered[90]['teacher'],'teacher printed at CSV position; other students appended');
check(!isset(roster::sheet_order($participants,$students,[],[90=>true])[90]),'unlisted teacher not automatically printed');
$dual=$students+[90=>$teacher];
check(!isset(roster::sheet_order($participants,$dual,[],[90=>true])[90]),'unlisted dual-role teacher not automatically printed');
$ordered=roster::sheet_order($participants,$dual,[90,1],[90=>true]);
check(count($ordered)===4 && $ordered[90]['teacher'],'listed dual-role teacher printed once as teacher copy');
echo "BETA LOGIC TESTS PASSED\n";
