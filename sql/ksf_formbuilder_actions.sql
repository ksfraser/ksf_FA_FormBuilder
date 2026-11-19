-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_actions

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_actions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `form_id` INT(11) NOT NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `action_type` ENUM('create_lead','create_debtor','create_branch','create_contact','create_invoice','create_meeting','create_ticket','send_email','webhook') NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `conditions_json` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `form_id` (`form_id`),
    KEY `action_type` (`action_type`),
    KEY `sort_order` (`sort_order`),
    FOREIGN KEY (`form_id`) REFERENCES `0_ksf_formbuilder_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
