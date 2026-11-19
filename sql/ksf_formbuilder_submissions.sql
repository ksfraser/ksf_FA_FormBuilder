-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_submissions

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_submissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `form_id` INT(11) NOT NULL,
    `submitter_id` INT(11) DEFAULT NULL,
    `submitter_type` VARCHAR(50) DEFAULT NULL,
    `submitter_name` VARCHAR(255) DEFAULT NULL,
    `submitter_email` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('submitted','processed','error') NOT NULL DEFAULT 'submitted',
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `form_id` (`form_id`),
    KEY `submitter_id` (`submitter_id`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`form_id`) REFERENCES `0_ksf_formbuilder_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
