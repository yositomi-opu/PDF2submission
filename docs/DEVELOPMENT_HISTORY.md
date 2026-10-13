# Previous development notes

Historical notes migrated from MacMoodleSetup. Later entries supersede earlier behavior.

# Development handoff

Updated: 2026-10-10

## Beta CSV order includes all course roles (2026-10-10)

- Beta 0.2.02 / 2026101001 removes the mod/assign:submit capability filter
  from CSV identity lookup. All active enrolled course roles, including teachers,
  can be listed. Enrollment boundary and unknown/duplicate/ambiguous rejection
  remain. Updated Japanese/English help and mismatch wording.
- Follow-up: removed automatic final teacher sheet in beta. Listed participants
  print in CSV order; non-submitters, graders (including dual-role teachers), and
  the generator receive teacher QR markers. Unlisted teachers are not appended;
  unlisted submitters follow. Submission permissions remain unchanged.
- Regression checks cover all-role lookup, teacher CSV position, no unlisted
  teacher, dual-role deduplication, student ordering and teacher QR exclusion.
- User reports beta 0.2.01 QR generation worked on their Moodle. This follow-up
  has CLI regression/syntax checks, not a new live Moodle/browser test.
- Passed `php scripts/tests/pdf2_beta.php`, all beta PHP syntax checks, stable
  0.1.10 ZIP byte comparison and `git diff --check`.
- Distribution: separate beta 0.2.02 ZIP; stable plugin unchanged. Uncommitted.

## Separate PDF2submission Beta 0.2.01 (2026-10-10)

- New independent block_pdf2submissionbeta, version 2026101000, distributed as
  a separate ZIP. Stable plugin and its deployment are unchanged; Dockerfile does
  not auto-install this beta. No dependency on stable plugin for basic operation.
- Block directly downloads sheets on assignment selection, accepts a scan upload,
  and saves a per-course UTF-8 single-column idnumber/username CSV order. Resolve
  enrollment to user IDs; reject unknown/duplicate/ambiguous IDs before saving.
  Unlisted students follow natural ID order; teacher remains last. Shared course
  order persists in its own table, resettable; course deletion observer and privacy
  metadata/export/deletion included.
- Single scan/merge pass splits by signed assignment+student, then separate child
  review batches permit per-assignment confirmation with a right-hand selector.
  Restrict to same course and authorized assignments. Any invalid page blocks all
  children; QR-less continuations track both assignment and student, teacher QR
  excludes its continuations. Stable same-site signed QR accepted using its key;
  beta QR is not accepted by stable. Shared assignment lock serializes saves.
- Limits inherited from 0.1.10. Original stable files remain byte-identical to its
  ZIP. No real roster, PDF, credentials or private paths added to tracked files.
- CLI logic checks: php scripts/tests/pdf2_beta.php passes CSV/order, signed stable
  compatibility, invalid QR reset, duplicate/unknown IDs, same student in multiple
  assignments, teacher exclusion and 90-student/181-page decoded-data grouping.
  All PHP lint, Python syntax, XML parse, stable tests and diff whitespace checked.
- This Mac cannot start Docker (application not found; daemon socket absent).
  No beta Moodle install, browser interaction, real multi-assignment PDF processing,
  database/privacy runtime or load benchmark claimed. These remain required before
  production use. No commit/push in this turn.

## Batch capacity for roughly 90 students (2026-10-09)

- Version 2026100901 / 0.1.10: upload ceiling 100 MiB, validated in the
  filepicker and again before analysis; processor scan/merge each have 900-second
  deadlines with a 10-second forced-stop grace. PHP analysis budget is 1860 seconds
  for both stages. Scan Python CPU limit raised from 120 to 900 seconds; 1 GiB
  memory, per-page 15/10-second deadlines and 300 dpi remain unchanged.
- 80–90 students (about 180 pages at two pages each) is an operating target,
  not a tested throughput guarantee or hard student cap. Split 200-person classes
  into 2–3 batches at student boundaries. README explains independent PHP/Moodle/
  proxy size and timeout settings; no deployed server configuration changed.
- User reports the preceding 300 dpi release successfully processed a roughly
  24 MB scan. Reason the old form's 20 MiB setting did not reject it is unverified.
- Passed `php scripts/tests/pdf2_limits.php` (24/60/100 MiB accepted,
  100 MiB + 1 byte rejected, request budget), all plugin `php -l`, Python
  `ast.parse`, existing page_groups, teacher_sheets and student_order PHP tests,
  and `git diff --check`. Initial temporary test stub lacked Moodle exception's
  constructor signature; corrected stub and rerun passed.
- Prepared plugin-only 0.1.10 ZIP. No 90-person end-to-end Moodle load test or
  live installation performed. All plugin work remains uncommitted/unpushed.

## Scanned QR reading resolution (2026-10-09)

- Version 2026100900 / 0.1.9 changes Ghostscript QR analysis from 180 dpi to
  300 dpi grayscale. Original PDF pages, grouping, signatures, upload/time limits,
  and QR generation are unchanged. README documents the analysis resolution.
- Local diagnosis on a private 123-page scan using Ghostscript + ZBar decoded
  13 distinct new-format student codes at 180 dpi and 59 at both 300 and 400 dpi.
  Another QR was legacy-format; intentionally obscured QR codes are separate.
  These are decoding results, not Moodle signature verification or submissions.
  Private scan/decoded identities stay outside the repository and distribution.
- Post-edit checks passed: Python `ast.parse` for `cli/pdf.py`, `php -l` on
  `version.php`, `php scripts/tests/pdf2_page_groups.php`,
  `php scripts/tests/pdf2_teacher_sheets.php`,
  `php scripts/tests/pdf2_student_order.php`, and `git diff --check`.
- Distribution: 0.1.9 plugin ZIP, containing only the plugin directory.
  Live Ubuntu/Moodle upgrade and submission with this release not tested.
  Changes remain uncommitted/unpushed.

## Final teacher QR sheet (2026-10-07)

- Version 2026100701 / 0.1.8 appends one QR sheet for the logged-in sheet
  generator after the naturally sorted student roster. A dual-role generator is
  printed only once at the end. Same compact header, with localized teacher label.
- Teacher QR uses a separate signed P2ST1 payload with assignment/user IDs.
  Import verifies the signature and assignment and excludes that page plus its
  QR-less continuations. Next valid student QR resumes student grouping.
  Invalid/multiple/wrong-assignment QR still fails the batch and clears inheritance.
  Review shows excluded page numbers in Japanese/English. Student P2S1 compatibility
  and submission permission checks remain unchanged.
- Passed `php scripts/tests/pdf2_student_order.php`,
  `php scripts/tests/pdf2_page_groups.php`, and
  `php scripts/tests/pdf2_teacher_sheets.php` (PDF recorder, no real PDF rendering).
  Tests cover student order, teacher identity/last-page/dual-role deduplication,
  signed exclusion, continuation/resume, tampering and wrong assignment rejection.
  All plugin PHP files passed `php -l`; `git diff --check` passed.
- Packaged 0.1.8 ZIP. No installed Moodle files or user/course data modified.
  Browser/real PDF rendering/scanner/server upgrade remain unverified for this
  version. Changes remain uncommitted/unpushed.

## QR sheet student order (2026-10-07)

- Version 2026100700 / 0.1.7 sorts the student roster by trimmed idnumber,
  case-insensitive natural order, with missing IDs last. Ties use username then
  internal user ID. uasort preserves user-ID keys required by QR/import lookups.
- sheets iterates this roster; existing QR payloads, assignments and submissions
  are unchanged. Moodle-wide participant ordering is not changed.
- `php scripts/tests/pdf2_student_order.php` passed synthetic natural-order,
  blank-ID, tie-break, empty-roster and identity/key-preservation checks.
  `php -l` on service.php and version.php and `git diff --check` passed.
- Packaged 0.1.7 ZIP. No installed Moodle code was changed. PDF/browser and
  research-server upgrade have not been verified for this version.
- Changes remain uncommitted/unpushed.

## Empty submissions and optional overwrite (2026-10-06)

- Version 2026100606 / 0.1.6 allows retained empty submission records using
  Moodle submission_empty instead of rejecting all existing records.
- Review confirmation includes an unchecked per-batch overwrite checkbox. Only
  included students are replaced. No global override, no changes to other students.
  Moodle save_submission receives the existing timemodified for concurrency checks.
- Teacher permission checks remain. submissions_open is called with null submission
  to allow teacher replacement of finalized drafts while retaining enrollment,
  cutoff, explicit submission locks and gradebook locks. Existing files otherwise
  block registration. Permission/closed errors and existing-content errors separated.
- Actual isolated macmoodle-pdf-test integration passed: initial save/finalize,
  default refusal of existing contents, explicit overwrite of finalized submissions,
  one named PDF per submission, removal of files leaving submission records, then
  successful re-registration without overwrite. Prior QR/invalid/duplicate/privacy/
  student-denial checks also passed (INTEGRATION SUCCESS). All PHP lint passed.
- Tests use synthetic course/users only; no production code/data modified. Browser
  checkbox and 66-person batch still unverified. Existing grades/feedback require
  teacher review after replacement. Changes uncommitted/unpushed; 0.1.6 ZIP built.

## Confirmation session and PDF names (2026-10-06)

- Version 2026100605 / 0.1.5 releases the session lock after POST sesskey and
  assignment permission checks, before confirmation. PHP execution time limit
  removed for this registration request; reverse-proxy limits still apply.
- Successful POST redirects to batch GET; completed manifest shows saved count,
  avoiding refresh resubmission. Does not diagnose or fix the reported five-minute
  server delay; submission events / infrastructure may still take time.
- New submitted files and preview downloads use assignment title + idnumber,
  falling back to username then numeric Moodle ID, with filename sanitization and
  length limits. Existing paper.pdf submissions unchanged.
- All PHP syntax checks passed. Filename tests using actual Moodle clean_param
  and core_text passed for idnumber, username and numeric fallback. No 66-student
  runtime/session-concurrency test performed; research-server behavior unverified.
- Updated README and prepared 0.1.5 ZIP; no deployed code changed, no commit/push.

## QR-less continuation pages (2026-10-06)

- Version 2026100604 / 0.1.4: as explicitly requested, pages with no decoded QR
  inherit the immediately preceding identified student, in scan order. The review
  page lists inherited page numbers and explains that a missed new-student QR also
  inherits the prior student. Confirmation remains manual.
- A leading QR-less page is an error. Multiple/invalid/wrong-assignment/not-enrolled
  QR codes are errors and clear the current student until another valid QR occurs.
  Errors still block the batch. Older manifests without inherited metadata work.
- Standalone scripts/tests/pdf2_page_groups.php tests actual service::group_pages:
  continuations, leading/all blank pages, invalid/multiple/wrong-assignment QR,
  non-enrolled users and repeated students all passed. Test-only exception stub
  constructor was corrected after an initial fixture failure. No real data used.
- All plugin PHP files passed php -l. Browser warning display and physical scan
  end-to-end are unverified. Earlier notes requiring QR on every page are superseded.
- Prepared 0.1.4 ZIP. No installed server files changed; not committed or pushed.

## PDF scan page count and diagnostics (2026-10-06)

- Version 2026100603 / 0.1.3 removes the fixed 40-page ceiling in both scanning
  and merging. Keeps the 20 MiB upload limit, 240-second processor timeout and
  existing per-page / resource limits; no unlimited processing claim.
- Structured Python error codes distinguish encryption, oversized pages, missing
  pypdf/Ghostscript/ZBar, page timeout and invalid PDFs. PHP displays translated
  known reasons (including total timeout) and appends raw output to the private
  processor.log. README explains limits and diagnostic location.
- Tested current helper in the running PHP container's temporary directory only:
  synthetic 41-page PDF rasterized and all QR codes decoded; merged output had
  41 pages. Encrypted and invalid PDF error codes passed. All PHP files passed
  php -l; Python py_compile and git diff --check passed. No production plugin
  files, course records or submissions changed; browser error display and the
  user's failing PDF remain unverified.
- Distributable 0.1.3 ZIP generated. Changes remain uncommitted/unpushed.

## Compact QR worksheet header (2026-10-06)

- Version 2026100602 / 0.1.2: QR header reduced from 36 to 25 mm (69%), with a
  black square outline. Removed writing instructions and all ruled answer lines.
  Course, assignment and student information remain at the top for worksheet overlay.
- Explicit left alignment fixes TCPDF MultiCell's default justification stretching
  spaces in names. Name whitespace normalized to one space; Moodle name order retained.
- Verified service::sheets with a synthetic assignment and two synthetic students,
  without writing course/user data or changing the installed plugin. Generated two
  pages, rendered at 180 dpi using Ghostscript, decoded both QR values with ZBar.
  Poppler visual inspection confirmed Japanese headings, compact name spacing,
  square frame and blank answer area. Ghostscript in this container substitutes
  the non-embedded Japanese font poorly; Poppler rendered it correctly. Physical
  scans and cpdf overlays are not tested. All PHP files passed php -l.
- Built 0.1.2 plugin ZIP; no commit/push or server installation in this turn.

## PDF2submission assignment picker (2026-10-06)

- Version 2026100601 / 0.1.1: block title is PDF2submission in Japanese and English.
- Added a shared course assignment dropdown in the block and import page, using
  Moodle single_select; selection opens the QR generation / direct PDF upload page.
  Preserves course ID and current assignment; only assignments with grade permission
  are listed. Existing service authorization remains enforced on opening a task.
- Added upload heading, empty-list guidance and narrow-block select styling.
  README explains that the three-dot menu is for block settings, not PDF upload.
  WebDAV / automatic folder watching remains unsupported.
- Verification: all 14 PHP files passed php -l via docker compose exec -T web;
  checked single_select constructor/label API against the running Moodle 4.5 source;
  git diff --check passed. Browser appearance and research-server upgrade remain
  unverified. No running installation was changed in this turn.
- Built a distributable 0.1.1 ZIP with plugin files only. Changes remain uncommitted
  and unpushed, including the original PDF2submission addition.

## PDF2submission addition (2026-10-06)

- Added GPL-3.0-or-later block under plugins/blocks/pdf2submission, inspired by
  Toshihiro KITA's original workflow, rewritten for Moodle 4.5 / PHP 8.3. This is
  our beta implementation, not an upstream release. Runtime dependencies are
  Ghostscript, ZBar and Python pypdf; no old FPDI is bundled.
- Teachers/admins select an individual file-upload assignment, generate signed QR
  sheets, upload a scan, review student/page/PDF mappings and explicitly confirm.
  Every page needs a valid QR. Same student's pages merge in source order.
  Missing/multiple/old/different-site/wrong-assignment codes prevent registration.
- Moodle save_submission / submit_for_grading APIs handle proxy submissions,
  events and files. Existing submission records (including drafts) are refused.
  Course-scoped manage, grade and editothersubmission capabilities are required.
  No capabilities were globally broadened. Ordinary teachers need an explicit
  editothersubmission permission for the applicable course.
- Review files are private per uploader, expire in 24h and are removed by an hourly
  scheduled task. Privacy provider exports/deletes temporary PDFs. Confirm uses
  an assignment lock, revalidation and a DB transaction. No external processing.
- User clarified that old sheets may have been printed by an unavailable university
  Moodle. Those IDs cannot be mapped automatically to this site. No legacy mapping
  or import was added; old QR is rejected. Future rescue requires reviewed roster
  and assignment mapping, not a numeric-ID match.
- Backed up the regular Moodle before upgrading. Installed version 2026100600 in
  the regular environment with make setup start; STACK healthcheck passed, cron
  restarted, and existing data retained. Changes not committed/pushed yet.

Verification:
- Separate disposable Compose project macmoodle-pdf-test, separate DB/data and
  port. No real students or courses were used. scripts/tests/pdf2_integration.php
  requires MACMOODLE_PDF2_TEST=1; copy it into that disposable container and run
  php as www-data. It creates test courses/accounts and must not run on real data.
- Real raster QR decoding: two students, three pages with non-consecutive pages
  belonging to the same student. Passed splitting/merging, signing, legacy/wrong
  assignment rejection, unreadable-page rejection, teacher proxy save/finalize,
  stored PDF counts, duplicate batch rejection, preserving existing submissions,
  and denying student import. Privacy context discovery also passed.
- Real browser: login, assignment picker, mapping table and POST confirmation
  succeeded; success showed two registered submissions. File chooser automation
  failed to attach a file, so browser review data was prepared with the same service
  API. Browser upload end-to-end remains unverified; no upload success is claimed.
- All 13 PHP files passed php -l. Regular Moodle reported the installed plugin
  version and registered cleanup task. git diff --check passed.
- Physical scanner quality, Japanese UI/PDF visual layout, privacy export/delete,
  expiration cleanup execution, Moodle 5.x and old-plugin in-place upgrades remain
  unverified. No group submissions, unsigned legacy QR, background templates or
  unattended folder scanning in this beta.
