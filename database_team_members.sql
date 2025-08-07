-- Team Members Table
-- This table stores information about team members for each manufacturer

CREATE TABLE IF NOT EXISTS `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','member') NOT NULL DEFAULT 'member',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_manufacturer` (`email`, `manufacturer_id`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `role` (`role`),
  KEY `is_active` (`is_active`),
  KEY `last_login` (`last_login`),
  CONSTRAINT `fk_team_members_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add processed_by column to orders table to track which team member processed the order
ALTER TABLE `orders` ADD COLUMN `processed_by` int(11) NULL AFTER `manufacturer_id`;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `team_members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Insert sample team member data (optional - for testing)
-- INSERT INTO `team_members` (`manufacturer_id`, `first_name`, `last_name`, `email`, `password`, `role`, `is_active`) VALUES
-- (1, 'John', 'Manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1),
-- (1, 'Jane', 'Member', 'member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 1);
