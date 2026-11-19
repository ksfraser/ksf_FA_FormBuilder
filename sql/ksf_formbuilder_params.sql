-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_action_params

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_action_params` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `action_id` INT(11) NOT NULL,
    `target_field` VARCHAR(255) NOT NULL,
    `source_field` VARCHAR(255) NOT NULL,
    `transformation` ENUM('none','uppercase','lowercase','date_format','concat') NOT NULL DEFAULT 'none',
    `default_value` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `action_id` (`action_id`),
    KEY `target_field` (`target_field`),
    KEY `source_field` (`source_field`),
    FOREIGN KEY (`action_id`) REFERENCES `0_ksf_formbuilder_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
