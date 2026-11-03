# Managed forward migrations

This directory is reserved for migrations that will eventually be executed and verified by the managed CLI runner.

Until that runner is ready, owner-approved feature migrations are added to the existing numbered sequence in database/migrations/ and mirrored in database/fresh_install.sql. These manual migrations must be applied through a trusted local SQL client or CLI only after verifying the exact predecessor schema.

- Keep old migration files immutable; never rewrite or renumber them.
- Do not assume a migration ran because its file exists.
- Never apply database/fresh_install.sql over an existing database.
- Unknown or drifted database states must be inspected before applying an incremental migration.
- Keep the web admin diagnostic console read-only.
- When the managed runner is available, new forward migrations can move to this directory under manifest, checksum, backup and recovery rules.
