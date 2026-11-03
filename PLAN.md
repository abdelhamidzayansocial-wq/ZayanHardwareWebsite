# Zayan Hardware — Project Plan

> Single source of truth for project status, active work, backlog, bugs, decisions, verification, and next priority. Updated: 2026-11-02.
> Canonical repository: `abdelhamidzayansocial-wq/ZayanHardwareWebsite`; default branch: `main`. Latest verified main commit: `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e` (PR #52).

## 1. Project Overview

- Product: Zayan Hardware — Arabic-first RTL technical community for mobile hardware repair, board-level diagnosis, measurements, schematics, and repair outcomes.
- Stack: PHP 8.2+ MVC, MySQL/MariaDB, vanilla JavaScript/CSS, PWA/Web Push.
- Public document root: `public/`.
- Product boundaries: no CRM/customer management or mobile software-repair scope without explicit owner approval.
- Data preservation: technical posts, replies, diagnostics, repair outcomes, schematics, attachments, edit history, reputation, and approved knowledge must not be physically deleted.

## 2. Current Architecture

- Request flow: `public/index.php` → Core bootstrap/router → Controllers → Services/Models → PDO database → Views.
- Route declarations: `config/routes.php`.
- Runtime configuration: `config/database.php` reads `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` through `App::env()`.
- Canonical intended database name: `zayanhardware_db`.
- Environment template: `.env.example`; real `.env` is local/private and must never be committed or exposed.
- Private uploads/logs remain outside `public/`; database migrations are not automatically applied by the application.
- Fresh install: `database/fresh_install.sql` only against a newly created empty database. Existing-database adoption requires backup, isolated restore, schema comparison, and explicit reviewed plan.

## 3. Current Project Status

- GitHub `main` current verified head: `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e` (PR #52); its parents preserve the audio integration merge and documentation follow-up.
- PR #49 (`chore/repository-db-name-consolidation`) was squash-merged on 2026-11-02. It sets the DB default and sample environment to `zayanhardware_db`, aligns fresh-install and backup/restore examples, corrects the canonical repository reference, and adds this plan.
- Merge is source-control completion only; local `.env`, actual DB existence/connectivity, and local workspace state remain unverified.
- Owner's local checkout and folders under `/opt/lampp/htdocs/` are not accessible through the current GitHub connection. Local workspace inventory, data reconciliation, and archive/removal remain unverified and unperformed.
- No live database was queried or changed. No tests or GitHub Actions were run for this consolidation.

## 4. Active Tasks

### AUD-INT-001 — Integrate audio recording, real trimming, and publishing
- **Description:** Bring PR #48's audio recorder, real mono 16 kHz PCM16 WAV trimming, private original/rendered media separation, draft persistence, server validation, and publish rollback into the current main baseline.
- **Priority:** P1
- **State:** IN PROGRESS
- **Acceptance Criteria:** Audio source and schema changes are present on `main`; canonical DB defaults from PR #49 remain intact; duplicate lowercase `plan.md` is excluded; migration 020 follows the documented historical-migration policy; PHP/JS and disposable-DB tests pass on the exact current main SHA; browser/device acceptance is recorded before release.
- **Dependencies:** Browser/device acceptance; restore-tested disposable DB; review of migration policy and Actions budget.
- **Affected Files:** `.github/workflows/database-migrations.yml`, `app/controllers/DraftController.php`, `app/controllers/IssueController.php`, `app/models/PostDraft.php`, `app/services/AudioWavPolicy.php`, `app/services/DraftPublishingService.php`, `app/views/issues/create.php`, `database/fresh_install.sql`, `database/migrations/020_audio_original_and_rendered_files.sql`, `public/css/post-composer.css`, `public/js/audio-recorder.js`, `public/js/post-composer.js`, and focused PHP tests/docs.
- **Test Results (verified 2026-11-03):** Exact current `main` SHA `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e` passed all three GitHub Actions workflows: PHP syntax check (run `41577446468`), Graphify dependency graph (run `41577446626`), and database fresh-install integration (run `41577446614`). All three database matrix jobs passed on MySQL 8.0, MariaDB 10.4.32, and MariaDB 10.11; each ran `draft-audio-publish-integration.php` successfully, including audio publish metadata and rollback. PHP lint, all configured PHP regression steps, JS syntax, Graphify unit/generation/determinism checks also passed. The standalone `tests/php/audio-wav-policy.php` was not included in either workflow; its validation coverage was separately reviewed and exercised in an isolated local harness. Browser/device, real microphone permissions, and live-database behavior remain unverified.
- **Commit/PR:** Integration merge commit `ee66711032ebbf3dbc1d1ee2ecab25268a41f87b`; PR #51 merged into `main` as `7437b190335dc4bffba3d6a542b0372a4e85dbca`; source PR #48 also marked merged.
- **Implementation Notes:** The GitHub merge API rejected PR #48 because of conflicts. A merge commit was assembled on a dedicated branch with current main as first parent and the audio feature head as second parent. Feature code/schema/docs were overlaid while preserving PR #49's canonical DB config and docs; the duplicate lowercase `plan.md` was not carried over. Migration 020 was reviewed against `database/managed_migrations/README.md` and `docs/MIGRATION_READINESS.md`: its current location in `database/migrations/` is correct until the managed CLI runner is ready. It mirrors columns already present in `database/fresh_install.sql` for fresh installs and is intended only for a verified schema-at-019 upgrade. It has not been applied to any owner database.


### CONS-DB-001 — Canonicalize database name in tracked configuration
- **Description:** Use `zayanhardware_db` as the only active default in application DB configuration and the sample environment; align setup/backup documentation.
- **Priority:** P1
- **State:** COMPLETED (source changes merged; runtime unverified)
- **Acceptance Criteria:** `config/database.php` fallback is `zayanhardware_db`; `.env.example` uses the same value; fresh-install and backup/restore examples use the same name; no secret is committed. Local runtime/database confirmation remains separately tracked.
- **Dependencies:** Owner confirmation of canonical repository and DB name (confirmed 2026-11-02).
- **Affected Files:** `config/database.php`, `.env.example`, `database/FRESH_INSTALL.md`, `docs/DATABASE_BACKUP_RESTORE.md`.
- **Test Results:** No PHP lint, automated tests, database integration tests, local runtime checks, or Actions were run. Static review confirmed the updated defaults and setup docs; a disposable CI DB name `zayan_fresh_install_test` remains intentionally separate. Actual local DB existence/configuration is not verified.
- **Commit/PR:** PR #49 merged into `main` as `5d1dcf841db9c1a2fd9af2415bb3d3ea98512f36`: https://github.com/abdelhamidzayansocial-wq/ZayanHardwareWebsite/pull/49.
- **Implementation Notes:** Existing local `.env` is intentionally not modified by a GitHub source change. Do not rename/create/migrate a live DB as part of this task.

### CONS-LOCAL-001 — Reconcile local workspaces into the official checkout
- **Description:** Inventory `zayan_hardware`, zayanv1/zayanv2, backups, temporary workspaces, Git status, local branches, and untracked/modified files; compare unique changes and integrate only reviewed files.
- **Priority:** P1
- **State:** BLOCKED
- **Acceptance Criteria:** Full read-only inventory completed; preserve every owner change and private artifact; produce a path-by-path reconciliation manifest; integrate only reviewed unique work; verify diffs; retain a recoverable archive before any cleanup; official folder remains `/opt/lampp/htdocs/zayan_hardware`.
- **Dependencies:** Read/write access to the owner's local filesystem through an authorized Work/Computer Use session.
- **Affected Files:** Local workspaces and Git metadata; exact paths pending inventory.
- **Test Results:** Not run; local filesystem is not exposed to this session.
- **Commit/PR:** None.
- **Implementation Notes:** No folder has been moved, renamed, archived, or deleted. No `git reset`, `git clean`, force push, or overwrite is permitted.

### CONS-PLAN-001 — Maintain project plan as source of truth
- **Description:** Establish this 12-section plan and keep task lifecycle records current.
- **Priority:** P1
- **State:** IN PROGRESS
- **Acceptance Criteria:** All required sections exist; every active task includes required fields; completed/cancelled history is retained.
- **Dependencies:** None.
- **Affected Files:** `PLAN.md`.
- **Test Results:** Structural source review pending after commit.
- **Commit/PR:** Plan maintenance remains ongoing; PR #49 was merged into `main` as `5d1dcf841db9c1a2fd9af2415bb3d3ea98512f36`. This audit update is isolated on `audit/verify-main-audio-20261103`.
- **Implementation Notes:** This file is the authoritative project task ledger.

## 5. Backlog
### GIT-GUARD-001 — Protect main and require the quality-gate workflows
- **Description:** Configure repository rules for `main` so direct pushes/deletions are blocked and reviewed pull requests require PHP syntax, Graphify, and database integration checks.
- **Priority:** P1
- **State:** PENDING
- **Acceptance Criteria:** A repository ruleset or branch protection rule targets `main`; require pull requests and the three named status checks; block force pushes and branch deletion; verify the effective rules after saving.
- **Dependencies:** Owner approval for repository governance settings; confirm exact required-check names and merge policy.
- **Affected Files:** GitHub repository settings/rulesets; no application source files.
- **Test Results:** Read-only GitHub API inspection reports `main.protected=false` and repository rulesets `[]` as of 2026-11-03. No settings were changed.
- **Commit/PR:** None; repository settings task.
- **Implementation Notes:** This is a governance gap, not an application test failure. Keep Actions workflows enabled; do not require a check that cannot run while Actions quota is exhausted without a documented owner-approved fallback.

### CI-AUDIO-001 — Add standalone WAV policy test to PHP workflow
- **Description:** Add `tests/php/audio-wav-policy.php` to `.github/workflows/php-lint.yml` so RIFF chunk, padding, format, size, and duration validation remains a first-class CI regression check.
- **Priority:** P2
- **State:** PENDING
- **Acceptance Criteria:** Add one workflow step; preserve all existing jobs, triggers, matrix, and concurrency; verify the test passes on a final PR SHA after Actions budget/reset is confirmed.
- **Dependencies:** Confirm available Actions minutes/reset date or explicit paid-usage authorization.
- **Affected Files:** `.github/workflows/php-lint.yml`, `PLAN.md`.
- **Test Results:** The test passed in an isolated local harness; it is not currently invoked by the PHP workflow. Current main PHP and database workflows passed on SHA `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e`.
- **Commit/PR:** None.
- **Implementation Notes:** Do not trigger new PR workflows until Actions budget status is confirmed. Do not disable or delete any workflow.


### CONS-DB-002 — Audit all tracked database-name references
- **Description:** Search the complete tracked tree for active connection strings, environment defaults, setup scripts, examples, test configuration, and documentation that may still target legacy DB names.
- **Priority:** P1
- **State:** PENDING
- **Acceptance Criteria:** Categorize each match as active configuration, test fixture, historical record, or explanatory text; active config and connection commands use `zayanhardware_db`; historical evidence is not silently rewritten.
- **Dependencies:** Full Git tree/source search and local environment inspection.
- **Affected Files:** All DB/config/env-related tracked files, exact list pending.
- **Test Results:** Not run.
- **Commit/PR:** None.
- **Implementation Notes:** Initial focused updates cover the primary config and setup/backup examples; a complete source-wide audit remains outstanding.

### CONS-LOCAL-002 — Verify local DB target and application connectivity
- **Description:** Inspect local environment using redacted output and read-only DB identity/status checks; confirm the configured DB exists and matches the application schema.
- **Priority:** P1
- **State:** BLOCKED
- **Acceptance Criteria:** Confirm host/port/database identity without exposing credentials; verify schema/tables and migration status read-only; no live writes or migration execution.
- **Dependencies:** Local runtime and database access.
- **Affected Files:** Local `.env`, `config/database.php`, database metadata.
- **Test Results:** Not run.
- **Commit/PR:** None.
- **Implementation Notes:** Never rename, create, drop, migrate, or overwrite the live database without a separate reviewed plan and explicit approval.

### CONS-LOCAL-003 — Archive redundant workspaces safely
- **Description:** After reconciliation, archive redundant folders and verify the archive before considering removal.
- **Priority:** P2
- **State:** BLOCKED
- **Acceptance Criteria:** Every candidate folder has a reviewed inventory; archive is readable and checksum-verified; official checkout is complete; no unique changes or private media are lost; owner explicitly approves final deletion.
- **Dependencies:** CONS-LOCAL-001 and verified local archive destination.
- **Affected Files:** Local directories only, exact list pending.
- **Test Results:** Not run.
- **Commit/PR:** None.
- **Implementation Notes:** Do not delete folders during initial inventory or reconciliation.

## 6. Bugs & Technical Debt

- **DB-name drift:** Tracked default and sample environment previously used `zayan_hardware`; primary defaults were changed on the current branch, but full-tree/local audit remains pending.
- **Repository metadata drift:** `AI_CONTEXT.md` previously named a different GitHub owner; corrected to the owner-confirmed canonical repository on this branch.
- **Local/GitHub divergence:** Historical handoff says local checkout is on `local/audit-update-20261031` with modified/untracked files. Re-verify before any local Git operation.
- **Backup/media assurance:** Historical backup path/hash is not proof of current existence or successful restore; private-media backup remains unverified.

## 7. New Ideas & Feature Requests

- No new product feature requests were introduced by this consolidation task.
- Future workspace automation should generate a reconciliation manifest and checksum-verified archive before any cleanup.

## 8. Completed Tasks
### AUDIT-REPO-DIFF-001 — Compare both connected GitHub repositories and verify current main
- **Description:** Compare the connected primary repository with `zoba2012ahmed/zayan_hardware`, preserve all source, and verify the merged audio feature and CI evidence.
- **Priority:** P1
- **State:** COMPLETED
- **Acceptance Criteria:** Compare complete Git trees and blob SHAs; identify unique files; verify current main Actions; review audio migration policy; record exact test evidence without triggering unnecessary CI.
- **Dependencies:** Read access to both connected GitHub repositories.
- **Affected Files:** Documentation only; no product source or workflow YAML changed.
- **Test Results:** Primary tree 278 entries / 231 blobs; comparison tree 128 entries / 108 blobs. All 108 comparison blobs exist in primary; zero comparison-only files; 123 primary-only blobs; only README.md differs among shared file blobs. Current main SHA `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e` passed PHP, Graphify, and database integration workflows; three DB engines passed, including audio publish/rollback integration. Isolated WAV policy test harness passed; no live DB or browser/device test performed.
- **Commit/PR:** Documentation record on `audit/verify-main-audio-20261103`; no PR opened because Actions billing allowance could not be verified.
- **Implementation Notes:** No files were copied from the comparison repository because it contained no unique blobs. No source branches were deleted, no migrations were applied, and no workflows were modified or manually rerun.


- **CONS-DB-001 (partial source implementation, 2026-11-02):** Updated `config/database.php`, `.env.example`, `database/FRESH_INSTALL.md`, and `docs/DATABASE_BACKUP_RESTORE.md` to use `zayanhardware_db`. Verification remains pending; do not mark runtime connectivity as complete.
- **Repository identity correction (2026-11-02; superseded 2026-11-03):** `AI_CONTEXT.md` was updated to `zayanfordesign/ZayanHardwareWebsite` based on the then-recorded owner confirmation. The connected repository, merged PR history, and current canonical target were re-verified on 2026-11-03 as `abdelhamidzayansocial-wq/ZayanHardwareWebsite`; the earlier identity is historical and superseded.

## 9. Technical Decisions

- Canonical GitHub repository: `abdelhamidzayansocial-wq/ZayanHardwareWebsite` (verified against the connected GitHub account and merged PR history on 2026-11-03). Historical references to `zayanfordesign/ZayanHardwareWebsite` are stale and superseded.
- Official local folder, per owner: `/opt/lampp/htdocs/zayan_hardware`.
- Canonical database name requested by owner: `zayanhardware_db`.
- New work starts from latest `main` on a dedicated branch; do not work directly on `main`.
- Never overwrite owner-local changes or delete workspace folders before inventory, reconciliation, and verified archive.
- Keep secrets in local environment only; commit only safe templates.
- No destructive DB operations, migrations, or production-data writes as part of this consolidation.

## 10. Testing & Verification

- GitHub source changes were committed through the GitHub API on the feature branch.
- No PHP lint, automated tests, database integration tests, local runtime checks, or GitHub Actions were executed for this task.
- Required before merge: inspect complete branch diff; run targeted source checks when available; verify every active DB config reference; owner-local read-only DB identity/schema check.
- CI usage policy: read `docs/github-actions-policy.md` and verify current billing budget before triggering Actions. Do not assume the historical reset or spending budget is current.

## 11. GitHub References

- Repository: https://github.com/abdelhamidzayansocial-wq/ZayanHardwareWebsite
- Main baseline: `0d9a2f15645055d958adf583de284c97634d1eb7`
- Working branch: `chore/repository-db-name-consolidation`
- PR #49: https://github.com/abdelhamidzayansocial-wq/ZayanHardwareWebsite/pull/49 — merged as `5d1dcf841db9c1a2fd9af2415bb3d3ea98512f36`.
- PR #51: https://github.com/abdelhamidzayansocial-wq/ZayanHardwareWebsite/pull/51 — audio integration merged as `7437b190335dc4bffba3d6a542b0372a4e85dbca`.
- PR #52: https://github.com/abdelhamidzayansocial-wq/ZayanHardwareWebsite/pull/52 — documentation follow-up merged as `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e`.
- Current verification runs on `7baee0a19fc2147d8b7a46bc4b4d7717db776e0e`: PHP `41577446468`, Graphify `41577446626`, database matrix `41577446614`.
- PR #48 (audio recording/trimming): source was integrated on `integration/audio-feature-merge-20261103` as merge commit `ee66711032ebbf3dbc1d1ee2ecab25268a41f87b`. PR #51 merged the integration branch into `main` as `7437b190335dc4bffba3d6a542b0372a4e85dbca` on 2026-11-03. GitHub also marks source PR #48 merged through the second parent commit `ee66711032ebbf3dbc1d1ee2ecab25268a41f87b`.

## 12. Next Priority

Complete browser/device acceptance for microphone recording, pause/resume, trim/export, draft recovery/conflict handling, and published playback. Keep all live-database changes blocked until a schema-at-019 preflight, verified backup/restore, and explicit approval. Local workspace reconciliation remains blocked until authorized local filesystem access is available.
