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