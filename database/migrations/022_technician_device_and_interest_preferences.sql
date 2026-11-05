-- Migration 022: expanded technician device coverage and private interests.
-- Preconditions: reviewed schema after 021; technician_profiles.device_focus,
-- user_device_specializations.device_type and skill_domains exist; the new
-- user_interest_domains table does not. Existing device selections are preserved.
-- Impact: widens device identifiers and adds a private preference relation.
ALTER TABLE technician_profiles
    MODIFY device_focus VARCHAR(40) NULL;

ALTER TABLE user_device_specializations
    MODIFY device_type VARCHAR(40) NOT NULL;

INSERT IGNORE INTO skill_domains (slug, name_ar, sort_order) VALUES
('short_circuit', 'الشورتات', 10),
('storage', 'التخزين', 11),
('board_repair', 'إصلاح البورد واللحام', 12);

CREATE TABLE user_interest_domains (
    user_id BIGINT UNSIGNED NOT NULL,
    skill_domain_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, skill_domain_id),
    KEY idx_user_interest_domain (skill_domain_id, user_id),
    CONSTRAINT fk_user_interest_domain_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_interest_domain_skill FOREIGN KEY (skill_domain_id) REFERENCES skill_domains(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Postcondition: device_type is VARCHAR(40), and user_interest_domains exists.
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'user_device_specializations' AND COLUMN_NAME = 'device_type')
    OR (TABLE_NAME = 'technician_profiles' AND COLUMN_NAME = 'device_focus'));

SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'user_interest_domains';
