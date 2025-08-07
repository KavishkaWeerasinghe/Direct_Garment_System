-- Profile Management Database Schema
-- This file adds necessary tables and columns for profile management functionality

-- Add profile-related columns to manufacturers table if they don't exist
ALTER TABLE `manufacturers` 
ADD COLUMN IF NOT EXISTS `profile_photo` varchar(500) DEFAULT NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `role` enum('Owner','Manager','Staff') DEFAULT 'Owner' AFTER `profile_photo`,
ADD COLUMN IF NOT EXISTS `nic_number` varchar(20) DEFAULT NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `last_login`,
ADD COLUMN IF NOT EXISTS `is_deleted` tinyint(1) NOT NULL DEFAULT 0 AFTER `two_factor_enabled`,
ADD COLUMN IF NOT EXISTS `deleted_at` timestamp NULL DEFAULT NULL AFTER `is_deleted`;

-- Create manufacturing_login_history table for tracking login sessions
CREATE TABLE IF NOT EXISTS `manufacturing_login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Success','Failed','Blocked') NOT NULL DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `login_time` (`login_time`),
  KEY `status` (`status`),
  CONSTRAINT `fk_manufacturing_login_history_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX `idx_manufacturing_login_history_manufacturer_time` ON `manufacturing_login_history` (`manufacturer_id`, `login_time`);
CREATE INDEX `idx_manufacturing_login_history_status_time` ON `manufacturing_login_history` (`status`, `login_time`);

-- Add indexes to manufacturers table for profile queries
CREATE INDEX `idx_manufacturers_email_verified` ON `manufacturers` (`email_verified`);
CREATE INDEX `idx_manufacturers_role` ON `manufacturers` (`role`);
CREATE INDEX `idx_manufacturers_created_at` ON `manufacturers` (`created_at`);
CREATE INDEX `idx_manufacturers_last_login` ON `manufacturers` (`last_login`);

-- Create a view for profile statistics
CREATE OR REPLACE VIEW `profile_stats_view` AS
SELECT 
    m.id,
    m.first_name,
    m.last_name,
    m.email,
    m.role,
    m.created_at,
    m.last_login,
    DATEDIFF(NOW(), m.created_at) as account_age_days,
    COUNT(mlh.id) as total_logins,
    COUNT(CASE WHEN mlh.login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_logins_30d,
    COUNT(CASE WHEN mlh.login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_logins_7d,
    MAX(mlh.login_time) as last_activity
FROM manufacturers m
LEFT JOIN manufacturing_login_history mlh ON m.id = mlh.manufacturer_id AND mlh.status = 'Success'
WHERE m.is_deleted = 0
GROUP BY m.id;

-- Insert sample data for testing (optional - remove in production)
-- INSERT INTO manufacturing_login_history (manufacturer_id, ip_address, user_agent, login_time, status) VALUES
-- (1, '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW(), 'Success'),
-- (1, '192.168.1.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Success'),
-- (1, '192.168.1.3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Success'); 