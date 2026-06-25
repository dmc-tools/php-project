
-- Create Database
CREATE DATABASE IF NOT EXISTS `enterprise_os` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `enterprise_os`;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100),
  `description` TEXT,
  `unitPrice` DECIMAL(15, 2) NOT NULL,
  `unit` VARCHAR(50),
  `category` VARCHAR(100),
  `stockQuantity` INT NOT NULL DEFAULT 0,
  `minStockLevel` INT NOT NULL DEFAULT 5,
  `createdAt` DATETIME NOT NULL,
  `lastUpdated` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Salespersons Table
CREATE TABLE IF NOT EXISTS `salespersons` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `employeeId` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `commissionRate` DECIMAL(5, 2) DEFAULT 0.00,
  `department` VARCHAR(100),
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `createdAt` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Commission Rules Table
CREATE TABLE IF NOT EXISTS `commission_rules` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `logicType` VARCHAR(50) NOT NULL,
  `params` JSON,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Tax Rates Table (New)
CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `rate` DECIMAL(5, 2) NOT NULL,
  `description` TEXT,
  `isDefault` BOOLEAN DEFAULT FALSE,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Customers Table
-- Stores all client information, including their hardware assets.
CREATE TABLE IF NOT EXISTS `customers` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `payeeName` VARCHAR(255) NULL,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `address` TEXT,
  `assignedSalespersonId` VARCHAR(50),
  `commissionRuleId` VARCHAR(50),
  `companyName` VARCHAR(255),
  `totalOrders` INT DEFAULT 0,
  `status` ENUM('Active', 'Lead') DEFAULT 'Active',
  
  -- !! IMPORTANT DATABASE CHANGE !!
  -- The 'purchasedHardware' column must be of type JSON.
  -- This allows storing multiple hardware items (as an array of objects) for a single customer.
  -- The application frontend is responsible for converting the array to a JSON-formatted string
  -- using JSON.stringify() before sending it to the backend for an INSERT or UPDATE operation.
  -- Example of the string data that will be stored in this column:
  -- '[{"productId":"p-123","machineNumber":"MAC-001","purchaseDate":"2024-01-15"},{"productId":"p-456","machineNumber":"MAC-002","purchaseDate":"2024-02-20"}]'
  `purchasedHardware` JSON,
  
  `bankName` VARCHAR(255),
  `accountNumber` VARCHAR(100),
  `comment` TEXT NULL,
  `createdAt` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Users Table (New for Authentication)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('ADMIN', 'SALESPERSON', 'CUSTOMER', 'DEVELOPER') NOT NULL,
  `related_id` VARCHAR(50) DEFAULT NULL, -- Links to salespersons.id or customers.id
  `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Invoices Table
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` VARCHAR(50) NOT NULL,
  `invoiceNumber` VARCHAR(100) NOT NULL,
  `customerId` VARCHAR(50) NOT NULL,
  `salespersonId` VARCHAR(50),
  `period` VARCHAR(100),
  `date` DATE NOT NULL,
  `dueDate` DATE,
  `items` JSON NOT NULL,
  `totalAmount` DECIMAL(15, 2) NOT NULL,
  `taxLevel` ENUM('ITEM', 'TOTAL') DEFAULT 'ITEM',
  `status` ENUM('Paid', 'Pending', 'Overdue') DEFAULT 'Pending',
  `createdAt` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Company Details Table
CREATE TABLE IF NOT EXISTS `company_details` (
  `id` VARCHAR(50) PRIMARY KEY,
  `legalName` VARCHAR(255),
  `uen` VARCHAR(100),
  `address` TEXT,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `website` VARCHAR(255),
  `bankName` VARCHAR(255),
  `accountNumber` VARCHAR(100),
  `swiftCode` VARCHAR(100),
  `logoUrl` LONGTEXT
) ENGINE=InnoDB;

-- Seed Users for testing
INSERT INTO `users` (`username`, `password`, `role`, `related_id`) VALUES
('admin', 'pass123', 'ADMIN', NULL),
('tan_weilong', 'pass123', 'SALESPERSON', 's1'),
('marina_bay', 'pass123', 'CUSTOMER', 'c1'),
('dev_root', 'pass123', 'DEVELOPER', NULL);

-- Seed Company Details
INSERT INTO `company_details` (`id`, `legalName`, `uen`, `address`, `email`, `phone`, `website`, `bankName`, `accountNumber`, `swiftCode`)
VALUES ('main', 'Singapore Enterprise Solutions Pte Ltd', '202412345M', '10 Collyer Quay, #10-01 Ocean Financial Centre, Singapore 049315', 'finance@singapore-enterprise.sg', '+65 6789 0123', 'www.singapore-enterprise.sg', 'DBS Bank', '123-456789-0', 'DBSSG SG');

-- Seed Tax Rates
INSERT INTO `tax_rates` (`id`, `name`, `rate`, `description`, `isDefault`) VALUES
('tax_sg_gst_9', 'Singapore GST', 9.00, 'Standard Goods and Services Tax for Singapore.', 1),
('tax_zero', 'Zero-Rated', 0.00, 'Zero-rated supplies for exports or international services.', 0),
('tax_exempt', 'Exempt', 0.00, 'Tax-exempt supplies like financial services.', 0);
