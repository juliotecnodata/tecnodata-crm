SET NAMES utf8mb4;

ALTER TABLE sellers
 MODIFY COLUMN goal_mode ENUM('sales','collection','sales_collection') NOT NULL DEFAULT 'sales_collection';
