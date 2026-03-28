ALTER TABLE `notifications`
    MODIFY COLUMN `type` ENUM('appointment','payment','prescription','system','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
    ADD COLUMN `starts_at` DATETIME NULL AFTER `message`,
    ADD COLUMN `ends_at` DATETIME NULL AFTER `starts_at`,
    ADD COLUMN `created_by` INT NULL AFTER `reference_id`,
    ADD KEY `idx_notif_schedule` (`type`, `starts_at`, `ends_at`);
