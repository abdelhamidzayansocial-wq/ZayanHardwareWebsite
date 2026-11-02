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
- [x] Run PHP syntax checks for AudioWavPolicy and its focused test on Kali (reported by owner).
- [x] Run the WAV-policy test on Kali (reported by owner).
- [x] Run audio publish integration covering original/rendered separation, metadata and rollback restoration on the disposable database (reported by owner).
- [x] Run existing atomic content-write, draft publish and draft media-recovery integration tests on the disposable database (reported by owner).
- [x] Inspect the disposable fresh-install schema for the new audio columns (reported by owner).
- [ ] Run latest PHP/JavaScript syntax checks after the newest UI/recovery refinements.
- [ ] Browser-test record, pause/resume, microphone permission/device loss, IndexedDB crash recovery, real trim/re-trim preview, upload retry and full publish.
- [ ] Exercise audio-specific draft revision conflict and pending-delete recovery.
- [ ] Review the complete diff and open a draft PR for owner review.

## Database application notes

- New empty database: run database/fresh_install.sql once. It already includes migration 020's audio columns; do not run migration 020 afterward.
- Database already at migration 019: inspect the actual schema, then run database/migrations/020_audio_original_and_rendered_files.sql once.
- Never replay migrations 000–019 over the consolidated fresh-install schema.
- No live/hosted database has been accessed or modified by this feature branch.

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
- **Commit/PR:** Implementation commits: `212083989c36e1604fab468979a0813aaf38d1a4`, `20db34c3b640b00f08c9fa6a0a6cf242829cba8d`, `bf02b9feba8f7cd703c61ffebbbfba99e3c3a7fb`, `64b0d574e3eb1b018d9626ed4fe00fd288c0325d`, `d7ca9215a9688a1a5806c6989b95c7c39e609daa`, `ce832f1369edbaeff10dd7c9937a2447ce14844f`. No PR created; verification is still required.
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
- **Test Results:** Regression cases authored; not executed in this session. No PHP/JS/database/browser test result is claimed.
- **Commit/PR:** Commits `212083989c36e1604fab468979a0813aaf38d1a4`, `20db34c3b640b00f08c9fa6a0a6cf242829cba8d`, `bf02b9feba8f7cd703c61ffebbbfba99e3c3a7fb`, `64b0d574e3eb1b018d9626ed4fe00fd288c0325d`, `d7ca9215a9688a1a5806c6989b95c7c39e609daa`. No PR yet.
- **Implementation Notes:** Browser recording duration is decoded from the source blob. Existing trim percentages are normalized against decoded duration when re-rendering. Editor state version 2 identifies `decoded-audio`; server validation uses 5 ms for v2 and retains 1500 ms for legacy v1.

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
- This session: source review and GitHub diff inspection only. New regression cases for exact duration, 5 ms boundary, material mismatch, and rollback restoration were authored but not run.
- A clean clone attempt failed with exit code 128 because this environment could not resolve github.com. The local Kali/XAMPP runtime is not accessible from this session.
- Required before PR: PHP syntax, JavaScript syntax, focused PHP integration tests against the disposable database, browser/device acceptance, draft revision conflict and pending-delete recovery, and complete diff review.

## 11. GitHub References
- Repository: `zayanfordesign/ZayanHardwareWebsite`
- Active branch: `feature/post-audio-real-trimming`
- Base: `main` at `0d9a2f15645055d958adf583de284c97634d1eb7`
- Latest plan commit before canonicalization: `cbf8c5864b5d109743acad84de3f2f737c91bd58`
- Current branch comparison before this plan refresh: 64 commits ahead of main and 0 behind; this documentation commit adds one more.
- Latest verified branch head before this plan refresh: `04926334da4b28e71f966515e6acdbb3ca971690`.
- No open PR; no CI status checks were recorded for the current branch head.

## 12. Next Priority
Complete runtime verification for the audio-only workflow: PHP/JS syntax, WAV-policy and publish integration tests on the disposable database, then browser acceptance for recording, pause/resume, microphone loss, real trim/re-trim, draft recovery, upload retry, and publish. Do not open a PR, merge, or deploy until these checks and owner review pass.
