-- Migration 021: optional structured hashtags on technical posts.
-- Preconditions: the selected database is at the reviewed pre-021 schema;
-- issues exists and issues.tags_json does not exist. Do not replay this file.
-- Impact: additive nullable metadata only; existing posts remain unchanged.
ALTER TABLE issues
    ADD COLUMN tags_json LONGTEXT NULL;

-- Postcondition: this query must return exactly one row with COLUMN_NAME=tags_json.
SELECT COLUMN_NAME, DATA_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'issues'
  AND COLUMN_NAME = 'tags_json';
