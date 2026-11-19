-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_forms

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_forms` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `status` ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `submit_button_label` VARCHAR(255) DEFAULT NULL,
    `submit_message` TEXT,
    `redirect_url` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`),
    KEY `status` (`status`),
    KEY `is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
