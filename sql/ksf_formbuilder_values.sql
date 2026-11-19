-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_submission_values

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_submission_values` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `submission_id` INT(11) NOT NULL,
    `field_id` INT(11) NOT NULL,
    `value` TEXT,
    `value_json` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `submission_id` (`submission_id`),
    KEY `field_id` (`field_id`),
    FOREIGN KEY (`submission_id`) REFERENCES `0_ksf_formbuilder_submissions` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`field_id`) REFERENCES `0_ksf_formbuilder_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
