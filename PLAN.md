# Zayan Hardware — Development Plan

> Updated: 2026-11-02
> Active feature branch: feature/post-audio-real-trimming
> Base: main at 0d9a2f15645055d958adf583de284c97634d1eb7

## Delivery rules

- Work in focused feature branches and reviewable pull requests.
- Do not merge or deploy without the owner's explicit approval.
- Do not trigger GitHub Actions or paid CI while the usage budget is restricted.
- Run focused PHP/JavaScript/database checks locally when the project checkout is available.
- Keep draft revisions, IndexedDB recovery, media synchronization, pending deletes, and publish rollback behavior intact.
- Do not begin the next stage until the current stage's source review and local acceptance are complete.

## Stages

| Stage | Scope | Status |
|---|---|---|
| 1 | Post details editor: CodeMirror live preview, RTL/Arabic formatting, safe rendering, textarea fallback, draft compatibility | Complete and owner-approved |
| 2 | Audio recorder, review, actual trimming to mono 16 kHz PCM16 WAV, private original preservation, server validation and publishing integration | Implementation in progress; local verification pending |
| 3 | Published audio display: accessible player on issue details and compact feed indicator | Not started |
| 4 | Image Editor Architecture V2: DOM-free core, versioned state, coordinate transforms, tools, undo/redo, safe export | Not started |
| 5 | PWA/cache and final composer integration, cross-device and responsive acceptance | Not started |

## Stage 2 — Audio implementation checklist

- [x] Add schema fields for a separate private original audio file.
- [x] Include the same schema in the consolidated fresh-install SQL.
- [x] Add migration 020_audio_original_and_rendered_files.sql for databases already at schema 019.
- [x] Generate a real trimmed WAV in the browser instead of changing playback offsets only.
- [x] Preserve original recording separately from the rendered WAV in draft storage.
- [x] Validate WAV container, PCM format, channel count, sample rate, bit depth, duration and size server-side.
- [x] Require the confirmed trim metadata to match the uploaded WAV before publishing.
- [x] Publish the rendered WAV as the playable audio_path; retain the original in a private-only column.
- [x] Add microphone selection, browser-native MIME capability detection, and a live input-level meter.
- [ ] Re-run PHP syntax checks for AudioWavPolicy and its focused test from the current branch checkout; prior owner-reported result is not independently reproducible in this session.
- [ ] Re-run the WAV-policy test from the current branch checkout and capture its exit code.
- [ ] Run audio publish integration covering original/rendered separation, metadata and rollback restoration on the disposable database; not verified in this session.
- [x] Existing atomic content-write, draft publish and draft media-recovery integration tests passed in GitHub Actions on MySQL 8.0, MariaDB 10.4.32, and MariaDB 10.11 (run 41475496144). This does not execute the new audio-specific publish integration.
- [ ] Inspect the disposable fresh-install schema for the new audio columns and record the schema evidence; not verified in this session.
- [ ] Run latest PHP/JavaScript syntax checks after the newest UI/recovery refinements.
- [ ] Browser-test record, pause/resume, microphone permission/device loss, IndexedDB crash recovery, real trim/re-trim preview, upload retry and full publish.
- [ ] Exercise audio-specific draft revision conflict and pending-delete recovery.
- [x] Review the changed-file inventory and open draft PR #48 for owner review.
- [ ] Complete detailed review of all 23 changed files before merge.

## Database application notes

- **Owner's requested final target:** local phpMyAdmin database `zayan_hardware_clean_20261031` (`http://localhost/phpmyadmin/`). This is the intended final integration target, not permission to alter it during development.
- Treat this as an existing populated database. Owner supplied a SQL export ending in `COMMIT`; it shows existing users, issues, drafts, media, constraints and retention triggers. Owner clarified the shown accounts are test accounts. The `issues` definition in the supplied dump has the base audio columns (`audio_path`, MIME/size/duration/trim metadata) but lacks `audio_original_path`, `audio_original_mime_type`, and `audio_original_file_size`. No `app_migration_ledger` definition/rows were visible in the supplied dump. This is evidence consistent with a pre-020 schema, but the pasted export is not a verified restore-tested backup and does not prove the live phpMyAdmin schema is unchanged. Before DDL, perform read-only inspection of `issues` columns and migration ledger on the actual target and obtain a complete restorable backup.
- If it is a genuinely empty new database, run `database/fresh_install.sql` once. It already includes migration 020's audio columns; do not run migration 020 afterward.
- If live inspection confirms the three original-audio columns are all absent and the rest of the schema matches the 019 predecessor, apply `database/migrations/020_audio_original_and_rendered_files.sql` once after backup. If any column exists or the schema differs, stop and reconcile manually; do not use `IF NOT EXISTS` as a substitute for schema review. Do not infer migration state from the database name or pasted export alone.
- Never replay migrations 000–019 over the consolidated fresh-install schema. Do not execute DDL or mutate `zayan_hardware_clean_20261031` until the owner approves after inspection/backup and all application acceptance gates pass.
- No local/hosted database has been accessed or modified by this feature branch.

## Deferred work

Audio player display, Image Editor V2, and PWA integration are intentionally out of scope for the current Audio Stage 2 branch.

## Review follow-up — Audio duration consistency

### AUD-REV-001 — Bound rendered-WAV duration mismatch
- **Description:** Publish-time validation currently accepts an absolute difference of up to 1500 ms between the confirmed trim span and the inspected rendered WAV duration. The browser records source duration using elapsed wall-clock time, while the trimmer derives output duration from decoded audio frames. The current integration fixture covers only an exact 1500 ms match and does not exercise a mismatched-duration rejection.
- **Priority:** HIGH
- **State:** IN PROGRESS
- **Acceptance Criteria:**
  - Establish the real duration delta across supported MediaRecorder MIME types and browser decode behavior.
  - Use a narrowly bounded, documented tolerance or derive trim metadata from decoded source duration.
  - Add integration coverage for exact/rounding-boundary acceptance and material mismatch rejection, including short recordings.
  - Preserve rollback restoration and original/rendered file separation.
- **Dependencies:** Browser/runtime verification on supported browsers; disposable database integration environment.
- **Affected Files:** `app/services/DraftPublishingService.php`, `public/js/audio-recorder.js`, `public/js/post-composer.js`, `tests/php/draft-audio-publish-integration.php`.
- **Test Results:** Regression cases authored for exact match, the 5 ms decoded-duration boundary, and a material 500 ms mismatch with rollback restoration. Tests have NOT been executed. PHP/JavaScript syntax and browser/device tests remain pending because this session cannot access the project runtime; a clean clone attempt failed with exit code 128 (DNS/network resolution for github.com).
- **Commit/PR:** Implementation commits: `212083989c36e1604fab468979a0813aaf38d1a4`, `20db34c3b640b00f08c9fa6a0a6cf242829cba8d`, `bf02b9feba8f7cd703c61ffebbbfba99e3c3a7fb`, `64b0d574e3eb1b018d9626ed4fe00fd288c0325d`, `d7ca9215a9688a1a5806c6989b95c7c39e609daa`, `ce832f1369edbaeff10dd7c9937a2447ce14844f`. Draft PR #48: https://github.com/zayanfordesign/ZayanHardwareWebsite/pull/48. Runtime verification is still required.
- **Implementation Notes:** New trims derive source duration from decoded audio and normalize old draft trim positions against the decoded duration before rendering. Editor-state version 2 declares `decoded-audio`; publish-time tolerance is 5 ms for this version. Legacy version-1 drafts retain the previous 1500 ms tolerance for backward compatibility. This is source-level implementation only until runtime tests pass.

---

# Canonical Project Tracking

## 1. Project Overview
Zayan Hardware is a PHP/MySQL web application for hardware/device knowledge posts, help requests, draft composition, media editing, and publishing. The public document root is `public/`. Repository guidance in `README.md`, `AGENTS.md`, and `AI_CONTEXT.md` governs implementation and environment safety.

## 2. Current Architecture
- Server: PHP 8.2+, application controllers, services, models, and MySQL persistence.
- Client: browser JavaScript and CSS for post composition, draft editing, media capture, and editing.
- Media: private draft and issue storage; authenticated routes mediate access.
- Draft lifecycle: revision-checked draft state and media uploads, followed by transactional publishing with file-move rollback/recovery.
- Database: consolidated `database/fresh_install.sql` for new installs; numbered migrations for existing installations.

## 3. Current Project Status
- Stage 1 — Post details editor: complete and owner-approved.
- Stage 2 — Audio recorder and real trimming: implementation updated on the feature branch; runtime verification is still pending.
- Stage 3 — Published audio player/feed indicator: not started.
- Stage 4 — Image Editor Architecture V2: not started.
- Stage 5 — PWA/cache and final composer integration: not started.
- No production database or hosted deployment was modified.

## 4. Active Tasks
### AUD-REV-001 — Align rendered audio duration with decoded source duration
- **Description:** Make trim coordinates and duration metadata derive from decoded audio frames, while retaining compatibility for older drafts.
- **Priority:** HIGH
- **State:** IN PROGRESS
- **Acceptance Criteria:** New trim state is explicitly versioned; decoded source duration is authoritative; 5 ms rounding tolerance is enforced for version-2 decoded metadata; legacy version-1 drafts retain compatibility; integration tests accept the 5 ms boundary and reject a material mismatch; rollback preserves both source and rendered files.
- **Dependencies:** PHP/JS runtime checks and browser acceptance on the project host.
- **Affected Files:** `public/js/audio-recorder.js`, `public/js/post-composer.js`, `app/services/DraftPublishingService.php`, `tests/php/draft-audio-publish-integration.php`.
- **Test Results:** Regression cases authored. JavaScript parser checks passed for `public/js/audio-recorder.js` and `public/js/post-composer.js`. PHP 8.4 syntax checks passed for the isolated branch-fetched `AudioWavPolicy.php` and `audio-wav-policy.php`; the focused WAV-policy suite passed with exit code 0. The disposable-DB publish integration and browser/device tests remain unrun.
- **Commit/PR:** Commits `212083989c36e1604fab468979a0813aaf38d1a4`, `20db34c3b640b00f08c9fa6a0a6cf242829cba8d`, `bf02b9feba8f7cd703c61ffebbbfba99e3c3a7fb`, `64b0d574e3eb1b018d9626ed4fe00fd288c0325d`, `d7ca9215a9688a1a5806c6989b95c7c39e609daa`, `ce832f1369edbaeff10dd7c9937a2447ce14844f`, `7080b45934ba8b5e4fe621b4e0d071b5113d30c7`, `8eae9c96ce077bdb7d31cfa36c6748ac782830e5`. Draft PR #48: https://github.com/zayanfordesign/ZayanHardwareWebsite/pull/48; runtime verification is still required.
- **Implementation Notes:** Browser recording duration is decoded from the source blob. Existing trim percentages are normalized against decoded duration when re-rendering. Editor state version 2 identifies `decoded-audio`; server validation uses 5 ms for v2 and retains 1500 ms for legacy v1. Recorder stop events are isolated to their originating MediaRecorder/chunk list. A generation token invalidates pending microphone permission requests on reset and prevents overlapping startup/late streams from replacing the current session. Queued `dataavailable` events now check that token before persisting chunks or progress, preventing a deleted/stale recording from repopulating the next audio session.

## 5. Backlog
### AUD-UI-003 — Published audio player and feed indicator
- **Description:** Add an accessible audio player to issue details and a compact audio indicator in feed cards.
- **Priority:** MEDIUM
- **State:** PENDING
- **Acceptance Criteria:** Keyboard-accessible controls, correct private/public authorization behavior, responsive layout, and no exposure of original private recordings.
- **Dependencies:** Stage 2 completion and owner acceptance.
- **Affected Files:** To be mapped before implementation across issue/feed views, controllers, CSS, and JavaScript.
- **Test Results:** Not started.
- **Commit/PR:** None.
- **Implementation Notes:** Play only the published rendered audio path.

### IMG-ARCH-004 — Image Editor Architecture V2
- **Description:** Refactor image editing into a DOM-free core with versioned state, coordinate transforms, tools, undo/redo, and safe export.
- **Priority:** MEDIUM
- **State:** PENDING
- **Acceptance Criteria:** Unit-testable core, stable state serialization, coordinate correctness, bounded safe export, and backward-compatible draft recovery.
- **Dependencies:** Stage 2 acceptance; architecture review before implementation.
- **Affected Files:** To be mapped during planning.
- **Test Results:** Not started.
- **Commit/PR:** None.
- **Implementation Notes:** Keep UI adapters separate from transformation logic.

### PWA-INT-005 — PWA/cache and final composer integration
- **Description:** Integrate composer/media behavior with PWA caching and complete cross-device/responsive acceptance.
- **Priority:** MEDIUM
- **State:** PENDING
- **Acceptance Criteria:** No stale application shell or draft data loss; explicit offline behavior; cross-device and responsive tests pass.
- **Dependencies:** Stages 2–4 accepted.
- **Affected Files:** To be mapped during planning.
- **Test Results:** Not started.
- **Commit/PR:** None.
- **Implementation Notes:** Avoid caching private draft media or authenticated responses in shared caches.

## 6. Bugs & Technical Debt
### AUD-REV-002 — Close waveform decoder on every exit path
- **Description:** Ensure the temporary AudioContext used to rebuild a waveform from a restored audio blob is closed after both successful decoding and decoding failures.
- **Priority:** LOW
- **State:** COMPLETED
- **Acceptance Criteria:** AudioContext cleanup runs in a finally block; decode failures retain the existing waveform fallback; JavaScript parses successfully.
- **Dependencies:** None.
- **Affected Files:** `public/js/audio-recorder.js`.
- **Test Results:** JavaScript parser check passed using the exact GitHub branch source; source assertions confirmed the decoder is closed in finally and no longer only on the success path.
- **Commit/PR:** `e11633fb81bdbc9a91a3e9fadfe76f5f37fcff8c`; no PR yet.
- **Implementation Notes:** Moved temporary AudioContext creation outside the try body and close it in finally, guarded against an already-closed context.

- **AUD-REV-001:** Previously broad 1500 ms publish-time duration tolerance. Version-2 decoded trim metadata now uses 5 ms; legacy version-1 remains at 1500 ms for compatibility until legacy draft behavior is separately reviewed.
- Runtime/browser acceptance for recording pause/resume, permission denial, device loss, IndexedDB recovery, upload retry, revision conflicts, pending-delete recovery, and complete publishing remains outstanding.

## 7. New Ideas & Feature Requests
- No additional owner-approved feature requests are recorded beyond the staged work above.

## 8. Completed Tasks
- Stage 1 post details editor — completed and owner-approved.
- Stage 2 core audio implementation — source changes include real mono 16 kHz PCM16 WAV trimming, separate private original/rendered files, WAV validation, microphone selection, and publish rollback handling. Final runtime acceptance is not marked complete.
- Security Phase 2 automated baseline — previously merged in PR #19; broader manual security review remains open as recorded in project security handoff documents.

## 9. Technical Decisions
- New empty database: apply `database/fresh_install.sql` once; do not replay migration 020 afterward.
- Existing schema at migration 019: inspect actual schema and apply migration 020 once.
- Never replay migrations 000–019 over the consolidated fresh-install schema.
- Original audio remains private and separate; published playback points to the rendered WAV.
- Version-2 audio trim metadata uses decoded source duration and a 5 ms tolerance. Version-1 metadata keeps the legacy 1500 ms tolerance to avoid breaking existing drafts.
- No CI/paid GitHub Actions, merge, or deployment without owner approval.

## 10. Testing & Verification
- Owner-reported earlier checks: AudioWavPolicy PHP syntax/focused test, audio publish integration, atomic content-write, draft publish, draft media-recovery, and disposable fresh-install schema inspection.
- GitHub Actions for PR #48: repository-wide PHP syntax check and JavaScript syntax check passed (run 41475496098); Graphify passed (run 41475496836); database fresh-install and existing integration suite passed on MySQL 8.0, MariaDB 10.4.32, and MariaDB 10.11 (run 41475496144; latest rerun 41475878529 also passed on all three database versions). The database workflow exercised existing draft publish, media recovery, and backup/restore checks. It does NOT invoke the new `tests/php/draft-audio-publish-integration.php` audio-specific test. The focused WAV-policy test previously passed in an isolated PHP 8.4 workspace (exit code 0), but that workspace was temporary and is no longer available for rerun. Browser/device acceptance remains unrun because this session has no browser/device runtime.
- A clean clone attempt failed with exit code 128 because this environment could not resolve github.com. The local Kali/XAMPP runtime is not accessible from this session.
- Required before merge (PR #48 opened as draft): disposable-database audio publish integration, browser/device acceptance, audio-specific draft revision conflict and pending-delete recovery, and complete detailed diff review. Repository-wide PHP and JavaScript syntax checks and the existing DB integration matrix have passed in GitHub Actions.

## 11. GitHub References
- Repository: `zayanfordesign/ZayanHardwareWebsite`
- Active branch: `feature/post-audio-real-trimming`
- Base: `main` at `0d9a2f15645055d958adf583de284c97634d1eb7`
- Latest branch comparison: 84 commits ahead of `main`, 0 behind; 23 changed files.
- Draft PR: https://github.com/zayanfordesign/ZayanHardwareWebsite/pull/48 (Audio recording, real trimming, and publishing).
- Latest PR head before this tracking update: `ad2f26a69804cddbbced25f2dae00fbfbc778324`.
- GitHub reports `mergeable=true`, `mergeable_state=clean`. GitHub Actions PHP/JS lint, Graphify, and DB matrix completed successfully; latest database run `41475878529` passed all three matrix jobs. No additional commit statuses were reported.
- PR remains draft because audio-specific publish integration, browser/device acceptance, audio-specific revision/pending-delete recovery, and complete detailed diff review are not complete.
- Source review confirmed the publishing service requires a single audio item in audio mode, checks rendered WAV format and duration against versioned editor state, moves original and rendered files separately, and restores moved files after confirmed pre-commit rollback. Runtime/database verification remains outstanding.

## 12. Next Priority
- Database handoff evidence: owner supplied a partial SQL dump excerpt confirming populated `users`, `user_device_specializations`, `post_drafts`, `post_draft_media`, `issues` indexes/constraints, and retention-related triggers. Owner clarified the shown accounts are test accounts. The excerpt does not include the `CREATE TABLE issues` column list or `app_migration_ledger` rows, so it cannot establish whether migration 020 is already applied; do not infer schema state from indexes alone.
PR #48 is open as a draft. Next: run `draft-audio-publish-integration.php` against the disposable test DB, exercise browser/device and audio-specific revision/pending-delete recovery, and finish detailed diff review. Then convert PR #48 from draft and merge only after these gates pass. Separately inspect the live target schema and verify a complete restore-tested backup before applying migration 020 to `zayan_hardware_clean_20261031`.
