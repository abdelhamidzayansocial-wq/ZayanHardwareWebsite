# Post composer, device catalog and resilient drafts

This specification applies to the Zayan Hardware post-creation flow. It is intentionally additive: existing issue IDs, comments, replies, diagnostic history and help-request workflows remain intact.

## Agreed publishing experience

The current design is a single-page composer, not a multi-page wizard. The user chooses a content type from compact cards and writes in one unified visual surface. Title and description remain separate structured values for compatibility, but are visually grouped in one bordered editor card. The device/model catalog and technical category are no longer required to publish; legacy selections are preserved when an older draft is resumed.

- The title is the first, visually prominent line inside the writing surface; the body follows beneath it in the same card.
- Text, numbered/bulleted Markdown lists, and inline formatting use the existing CodeMirror 6 editor. Numbered and bulleted lists are written directly in the content; there is no separate steps form in the new composer.
- Images/video are added from the attachment control at the bottom of the same writing surface. The existing private-media pipeline, image editor, audio recorder, and draft recovery remain in place.
- Up to five optional hashtags can be added as chips. Arabic and Latin letters, digits, and underscores are accepted. Tags are saved with draft metadata and stored as JSON on the published issue. They are displayed on the issue page and included in the content-only technical search. Search also covers titles, descriptions, device/model names and codes, replies, diagnostic updates, and approved repair outcomes; technician profiles are never returned as search results.
- The current public content-type choices remain “معلومة فنية” and “طلب مساعدة” to preserve the existing domain model. Do not silently add a new database content type without a reviewed migration and corresponding status/workflow behavior.
- Device/model/category metadata may still be retained for older drafts or future optional enrichment, but a technician can publish without selecting a device or technical category.

## Post-details editor behavior

- The post-details editor keeps CodeMirror 6, its live preview, undo/redo history, keyboard navigation and textarea synchronization for form submission and draft persistence.
- The formatting toolbar is intentionally removed. Markdown is typed directly: `**text**` and `*text*` render bold in the live preview while the raw Markdown remains in the stored description.
- Bulleted (`- `) and numbered (`1. `) lists continue on Enter. Pressing Enter on an empty list item exits the list.
- Numbered and bulleted lists are typed directly in the details editor. The editor continues lists on Enter and exits an empty list item on a second Enter; there is no separate steps widget in the new composer.

## Draft and storage architecture

- SQL stores draft ownership, revision, timestamps and structured metadata only. Binary images, video and audio are never stored as SQL BLOBs or Base64 strings.
- Private server storage holds draft media and published originals/derivatives outside the public web root.
- IndexedDB holds the current draft payload and pending File/Blob objects on the current device. It is a local recovery copy, not the canonical account record.
- The authenticated server draft is the cross-device source of truth. Debounced saves send structured changes and media uploads separately.
- Every draft has a monotonically increasing revision. A stale client receives a conflict response with the latest server revision/payload; it must not silently overwrite newer edits.
- Media upload uses allow-listed MIME detection, random storage names, size limits, ownership checks and private download routes. Draft media is promoted to issue attachments on publish rather than copied into duplicate permanent files.
- Refresh recovery loads local IndexedDB first for immediate restoration, then reconciles with the server revision. If both contain different newer edits, the user is shown a conflict/recovery choice rather than losing either copy.
- Service worker caching is limited to static assets. Draft payloads, account pages, media upload responses and private media are never cached by the service worker.
- “مسوداتي” lists the member's drafts, last-edit time and sync state, and lets the owner resume or delete a draft. Drafts are not silently deleted while active.

## Database migration notes

Migration 012 creates the manufacturer/model catalog, moderated model suggestions, structured steps/audio fields, media editor metadata and revisioned drafts. It is only a migration file for review; it must not be run until the schema diff is reviewed and a database backup is available.

## Validation required before merge

- Verify the unified composer is one responsive card on desktop and mobile; confirm title/body separation is visual only and that old drafts restore correctly.
- Test tags with Arabic/Latin text, invalid punctuation, duplicates, max-five enforcement, local/server draft recovery, publish, public rendering, and hashtag search.
- Confirm a help request publishes without manufacturer, model, or category, and older drafts with selected model metadata still publish.
- Test the expanded registration device cards and favorite-domain cards; verify device categories are visible on the public profile while favorite domains are not returned by public profile/search queries.
- Apply migrations 021 and 022 to disposable MySQL 8 and supported MariaDB versions; verify their preconditions/postconditions and fresh-install equivalence.
- Run focused PHP/JS syntax checks and the existing draft/media/audio/registration test suites.

- Apply migrations 001–012 to a disposable database and verify foreign keys and JSON columns on the exact MySQL/MariaDB version used by hosting.
- Confirm old `help_request`, `technical_post` and `discussion` rows still render; confirm old attachments and inline media still work.
- Exercise model search by both commercial name and model code; submit and moderate a model suggestion using two roles.
- Test text/audio exclusivity, sequential steps, image/video type and size validation, image annotation round-trip and audio pause/resume/trim.
- Test draft refresh recovery, offline edits, retry, cross-device resume and revision conflict with two simultaneous clients.
- Verify private storage cannot be served directly and that only the owning member can read/update/delete a draft or its media.
- Test desktop, Android and iOS/PWA layouts and recorder capability differences. Do not claim microphone recording works in a browser context until permission, secure-context and MIME support are checked.