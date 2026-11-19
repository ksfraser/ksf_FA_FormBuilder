-- ksf_FA_FormBuilder module database schema for FrontAccounting
-- Table: 0_ksf_formbuilder_fields

CREATE TABLE IF NOT EXISTS `0_ksf_formbuilder_fields` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `form_id` INT(11) NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `field_type` ENUM('text','email','number','select','radio','checkbox','textarea','date','module_link') NOT NULL DEFAULT 'text',
    `source_module` VARCHAR(100) DEFAULT NULL,
    `source_field` VARCHAR(255) DEFAULT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `options_json` TEXT,
    `default_value` TEXT,
    `placeholder` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `form_id` (`form_id`),
    KEY `field_name` (`field_name`),
    KEY `field_type` (`field_type`),
    KEY `source_module` (`source_module`),
    FOREIGN KEY (`form_id`) REFERENCES `0_ksf_formbuilder_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
