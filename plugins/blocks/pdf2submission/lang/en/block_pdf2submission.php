<?php
// SPDX-License-Identifier: GPL-3.0-or-later
$string['pluginname'] = 'PDF2submission';
$string['pdf2submission:addinstance'] = 'Add a paper submission block';
$string['pdf2submission:manage'] = 'Import paper submissions on behalf of students';
$string['manage'] = 'Print sheets / Import scanned PDF';
$string['intro'] = 'Choose an individual file-upload assignment. Print one QR sheet per student, scan completed sheets as a PDF, then review before submitting. Up to 100 MB per upload; no fixed page-count limit. Each student needs a QR code on their first page. Pages without a readable QR code are attached to the preceding student.';
$string['scan'] = 'Scanned PDF';
$string['analyse'] = 'Read QR codes and review';
$string['sheets'] = 'Download student QR sheets';
$string['sheetnote'] = 'Write below this line. Keep the QR code clear. Each student needs a QR code on their first page.';
$string['studentcount'] = 'There must be between 1 and 500 active enrolled students with submission permission.';
$string['pages'] = 'Pages in uploaded PDF';
$string['preview'] = 'Download and check';
$string['confirmnote'] = 'Check every student and PDF before submitting. Select overwrite to replace existing submissions. This submits on behalf of the listed students.';
$string['confirm'] = 'Confirm and register submissions';
$string['saved'] = 'Registered submissions for {$a} students.';
$string['unreadable'] = 'Pages {$a}: no preceding identified student, multiple QR codes, or an invalid QR code (old version, different site or assignment). Nothing has been submitted. Correct the sheets or scan and upload again.';
$string['badqr'] = 'The QR code does not belong to this site and assignment.';
$string['unsupported'] = 'Use an individual assignment with file submissions enabled. Group submissions are not supported.';
$string['notconfigured'] = 'The signing key is missing. Re-run plugin setup.';
$string['invalidbatch'] = 'This scan is missing, expired, or belongs to another account or assignment.';
$string['processfailed'] = 'PDF processing failed. Use an unencrypted PDF up to 100 MB and standard paper sizes. Check Ghostscript, ZBar and pypdf installation if this persists.';
$string['busy'] = 'Another import is running. Try again shortly.';
$string['cannotconfirm'] = 'The scan has errors or has already been submitted.';
$string['existing'] = '{$a}: a submission already exists. Select overwrite to replace it. No submissions from this batch were saved.';
$string['filesize'] = 'A generated PDF exceeds the assignment file-size limit or is missing.';
$string['pdftype'] = 'This assignment does not allow PDF submissions.';
$string['savefailed'] = 'Moodle could not save the batch. No submissions from this batch were saved.';
$string['cleanup'] = 'Remove temporary scanned papers';
$string['privacy:files'] = 'Final submissions are stored by Moodle Assignment and the file subsystem.';
$string['privacy:temporary'] = 'Scanned PDFs and student mappings are temporarily stored locally for review and automatically removed after 24 hours.';
$string['assignment'] = 'Assignment';
$string['chooseassignment'] = 'Choose an assignment';
$string['noassignments'] = 'No assignments are available. Create an assignment in this course and check your grading permissions.';
$string['pickernote'] = 'Choose an assignment to download QR sheets or upload a scanned PDF.';
$string['uploadheading'] = 'Upload scanned PDF';

$string['processfailedreason'] = 'PDF processing failed: {$a}';
$string['process_encrypted'] = 'The PDF is encrypted. Use an unencrypted PDF.';
$string['process_empty'] = 'The PDF contains no pages.';
$string['process_pagesize'] = 'Page {$a} exceeds the page-size limit (1700 points).';
$string['process_ghostscriptmissing'] = 'Ghostscript (/usr/bin/gs) was not found.';
$string['process_zbarmissing'] = 'ZBar (/usr/bin/zbarimg) was not found.';
$string['process_pypdfmissing'] = 'Python pypdf was not found. Check the python3-pypdf installation.';
$string['process_pagetimeout'] = 'Rendering or QR decoding timed out on page {$a}.';
$string['process_renderfailed'] = 'Ghostscript could not render page {$a}.';
$string['process_qrfailed'] = 'The QR reader failed on page {$a}.';
$string['process_invalidpdf'] = 'Reading, splitting or merging failed. Check the PDF and available server disk space.';
$string['process_timeout'] = 'Processing exceeded 900 seconds (15 minutes). Split the PDF into smaller batches.';
$string['process_environment'] = 'The processor could not run or terminated unexpectedly. Ask the administrator to check Python, timeout and memory limits.';

$string['inheritedpages'] = 'Pages {$a} had no readable QR code and were attached to the preceding student. A missed QR on another student\'s first page is treated the same way. Check the PDFs before confirming.';

$string['overwrite'] = 'Overwrite existing submission PDFs';

$string['overwritenote'] = 'Replace existing files only for students included in this batch. Other students are unchanged. Review existing grades and feedback afterwards.';

$string['noteditable'] = '{$a}: submissions are closed, locked, or proxy submission is not permitted.';

$string['teachercopy'] = 'Teacher copy';
$string['teacherignoredpages'] = 'Pages {$a} were excluded as teacher copies (including QR-less pages following a teacher QR).';

$string['uploadtoolarge'] = 'The PDF exceeds 100 MB. Split it between students and upload each batch separately.';
