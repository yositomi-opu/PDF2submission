# PDF2submission development

- Read README.md and HANDOFF.md first; inspect branch and uncommitted changes.
- This repository is the canonical development source for PDF2submission 1.x. The retired 0.1 line and final beta are preserved by tags and release ZIPs.
- Keep the stable 1.x component block_pdf2submissionbeta and its install directory unchanged for in-place Beta upgrades. Never reuse the retired block_pdf2submission identity; retain existing QR compatibility.
- Do not alter installed Moodle, user data, course data or submissions without a specific request.
- Never commit scans, rosters, credentials, tokens, .env files, backups or private absolute paths.
- Keep UI wording in Japanese and English. Preserve GPL and upstream attribution.
- Run make check and make package for releases. Update HANDOFF.md with actual results and remaining checks.
- Standalone tests do not prove Moodle/browser operation. The integration test is only for a disposable Moodle with synthetic users.
- Do not edit the former MacMoodleSetup copies as the development source. Updating its vendor copy is an explicit separate deployment action.
- Do not force-push, discard changes or remove working installations.
