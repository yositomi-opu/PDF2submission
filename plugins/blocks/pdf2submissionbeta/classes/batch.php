<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace block_pdf2submissionbeta;
defined('MOODLE_INTERNAL') || die();
class batch {
    /** Split by signed assignment AND student; invalid pages reset inheritance. */
    public static function partition(array $pages, array $rosters): array {
        $items=[]; $errors=[]; $previous=null;
        foreach ($pages as $page) {
            $number=(int)$page['page'];
            try {
                if ($page['codes']) {
                    if (count($page['codes'])!==1 || !preg_match('/^(P2S1|P2ST1):([1-9][0-9]*):([1-9][0-9]*):[0-9a-f]{64}$/D', $page['codes'][0], $m)
                        || !service::valid_signature($page['codes'][0])) { throw new \moodle_exception('badqr','block_pdf2submissionbeta'); }
                    $cmid=(int)$m[2]; $uid=(int)$m[3]; $teacher=$m[1]==='P2ST1';
                    if (!isset($rosters[$cmid]) || (!$teacher && !isset($rosters[$cmid][$uid]))) { throw new \moodle_exception('badqr','block_pdf2submissionbeta'); }
                    $previous=[$cmid,$teacher ? 0 : $uid];
                } else if ($previous===null) { throw new \moodle_exception('badqr','block_pdf2submissionbeta'); }
                [$cmid,$uid]=$previous;
                if (!isset($items[$cmid])) { $items[$cmid]=['groups'=>[], 'inherited'=>[], 'ignored'=>[]]; }
                if ($uid===0) { $items[$cmid]['ignored'][]=$number; continue; }
                $items[$cmid]['groups'][$uid][]=$number;
                if (!$page['codes']) { $items[$cmid]['inherited'][]=$number; }
            } catch (\moodle_exception $e) { $errors[]=$number; $previous=null; }
        }
        return ['items'=>$items,'errors'=>$errors];
    }
    public static function load(string $token, int $courseid): array {
        $file=service::directory($token).'/manifest.json';
        $data=is_file($file) ? json_decode(file_get_contents($file),true) : null;
        if (!$data || ($data['courseid']??0)!==$courseid || !isset($data['assignments']) || $data['expires']<time()) {
            throw new \moodle_exception('invalidbatch','block_pdf2submissionbeta');
        }
        return $data;
    }
    public static function analyse(\stdClass $course, string $token): void {
        $path=service::directory($token); service::validate_source($path.'/source.pdf');
        $pages=service::processor('scan',$path)['pages'];
        $rosters=[]; $names=[];
        // Authorization before reading any roster or exposing any assignment name.
        foreach ($pages as $page) {
            foreach ($page['codes'] as $code) {
                if (!preg_match('/^P2S(?:T)?1:([1-9][0-9]*):/', $code, $m) || !service::valid_signature($code)) { continue; }
                $cmid=(int)$m[1]; if (isset($rosters[$cmid])) { continue; }
                $a=service::assignment($cmid);
                if ((int)$a->get_course()->id!==(int)$course->id) { throw new \moodle_exception('crosscourse','block_pdf2submissionbeta'); }
                $rosters[$cmid]=service::students($a); $names[$cmid]=$a->get_instance()->name;
            }
        }
        $split=self::partition($pages,$rosters);
        $manifest=['courseid'=>(int)$course->id,'expires'=>time()+DAYSECS,'assignments'=>[], 'groups'=>[], 'errors'=>$split['errors']];
        $jobs=[]; $destinations=[]; $children=[];
        try {
            foreach ($split['items'] as $cmid=>$item) {
                $child=bin2hex(random_bytes(16)); $dir=service::directory($child); mkdir($dir,0700); $children[]=$dir;
                $data=$item+['cmid'=>(int)$cmid,'expires'=>$manifest['expires'],'errors'=>$split['errors'],'done'=>false];
                foreach ($item['groups'] as $uid=>$numbers) {
                    $job=count($jobs)+1; $jobs[$job]=$numbers; $destinations[$job]=$dir.'/user-'.$uid.'.pdf';
                    $manifest['groups'][$uid]=array_merge($manifest['groups'][$uid]??[],$numbers);
                }
                file_put_contents($dir.'/manifest.json',json_encode($data),LOCK_EX);
                $manifest['assignments'][$cmid]=['token'=>$child,'name'=>$names[$cmid]];
            }
            // One bounded merge process for every assignment in this upload.
            file_put_contents($path.'/groups.json',json_encode((object)$jobs),LOCK_EX);
            if ($jobs) { service::processor('merge',$path); }
            foreach ($destinations as $job=>$target) {
                if (!rename($path.'/user-'.$job.'.pdf',$target)) { throw new \moodle_exception('processfailed','block_pdf2submissionbeta'); }
            }
            file_put_contents($path.'/manifest.json',json_encode($manifest),LOCK_EX);
        } catch (\Throwable $e) {
            foreach ($children as $dir) { remove_dir($dir); }
            throw $e;
        }
    }
}
