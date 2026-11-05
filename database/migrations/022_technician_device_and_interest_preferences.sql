-- Migration 022: expanded technician device coverage and private interests.
-- Additive schema change; existing device selections and skill ratings are preserved.
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
