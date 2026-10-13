<?php
// SPDX-License-Identifier: GPL-3.0-or-later
define('MOODLE_INTERNAL', true);
class moodle_exception extends Exception { public function __construct($code, $component) { parent::__construct($code); } }
require __DIR__ . '/../../plugins/blocks/pdf2submission/classes/service.php';
use block_pdf2submission\service;
$f=tempnam(sys_get_temp_dir(), 'pdf-limit-');
try {
 foreach ([24*1024*1024, 60*1024*1024, service::MAX_UPLOAD_BYTES, service::MAX_UPLOAD_BYTES+1] as $size) {
  $h=fopen($f,'w'); ftruncate($h,$size); fclose($h); clearstatcache(true,$f);
  $rejected=false;
  try { service::validate_source($f); } catch (moodle_exception $e) { $rejected=$e->getMessage()==='uploadtoolarge'; }
  if ($rejected !== ($size>service::MAX_UPLOAD_BYTES)) throw new Exception('Size boundary failure');
 }
 if(service::REQUEST_TIMEOUT < 2*service::PROCESS_TIMEOUT) throw new Exception('Request too short');
 echo "Upload boundaries and request budget: OK\n";
} finally {unlink($f);}
