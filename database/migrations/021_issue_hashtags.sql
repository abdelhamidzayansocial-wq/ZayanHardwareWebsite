-- Migration 021: optional structured hashtags on technical posts.
-- Safe additive change. Existing technical content and history are untouched.
ALTER TABLE issues
    ADD COLUMN tags_json LONGTEXT NULL;
