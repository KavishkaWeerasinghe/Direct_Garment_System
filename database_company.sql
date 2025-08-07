-- Create company_settings table for manufacturer business information
CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `district_province` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `business_email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook_page` varchar(255) DEFAULT NULL,
  `linkedin_page` varchar(255) DEFAULT NULL,
  `number_of_employees` int(11) DEFAULT NULL,
  `business_logo` varchar(500) DEFAULT NULL,
  `years_in_operation` int(11) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `description_bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `manufacturer_id` (`manufacturer_id`),
  KEY `business_type` (`business_type`),
  KEY `district_province` (`district_province`),
  CONSTRAINT `fk_company_settings_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data (optional)
INSERT INTO `company_settings` (`manufacturer_id`, `business_name`, `business_type`, `registration_number`, `business_address`, `district_province`, `contact_number`, `business_email`, `website`, `facebook_page`, `linkedin_page`, `number_of_employees`, `years_in_operation`, `certifications`, `description_bio`) VALUES
(1, 'Sample Garment Factory', 'Garment Manufacturer', 'REG123456789', '123 Factory Street, Industrial Zone', 'Western Province', '+94 11 234 5678', 'info@samplefactory.com', 'https://www.samplefactory.com', 'https://facebook.com/samplefactory', 'https://linkedin.com/company/samplefactory', 150, 10, 'ISO 9001:2015, Fair Trade Certified', 'Leading garment manufacturer with over 10 years of experience in producing high-quality clothing for international markets.'); 