-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 08:37 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ctebs`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrower_request`
--

CREATE TABLE `borrower_request` (
  `request_id` int(11) NOT NULL,
  `borrower_name` varchar(100) DEFAULT NULL,
  `borrower_address` varchar(255) DEFAULT NULL,
  `borrower_contact` varchar(15) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `borrow_quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrower_request`
--

INSERT INTO `borrower_request` (`request_id`, `borrower_name`, `borrower_address`, `borrower_contact`, `request_date`, `borrow_date`, `return_date`, `status`, `user_id`, `equipment_id`, `borrow_quantity`) VALUES
(4, 'Rayan Macapa-ar', 'Prk. Milestone brgy. San Miguel', '09093045239', '2026-10-10', '2026-10-10', '2026-10-11', 'Rejected', NULL, 3, 1),
(5, 'Rayan Macapa-ar', 'Prk. Milestone brgy. San Miguel', '09093045239', '2026-10-10', '2026-10-10', '2026-10-11', 'Returned', NULL, 1, 1),
(6, 'asdasd', 'asfasf', '09093045239', '2026-10-10', '2026-10-10', '2026-10-11', 'Returned', NULL, 2, 1),
(7, 'Rayan Macapa-ar', 'Prk. Milestone brgy. San Miguel', '09093045239', '2026-10-10', '2026-10-10', '2026-10-11', 'Returned', NULL, 3, 1),
(8, 'asdasd', 'asdasd', '23123122', '2026-10-10', '2026-10-24', '2026-10-31', 'Returned', NULL, 3, 25),
(9, 'Rayan Macapa-ar', 'Prk. Milestone brgy. San Miguel', '231231222', '2026-10-10', '2026-10-10', '2026-10-12', 'Returned', NULL, 3, 13),
(10, 'asdasd', 'dasdasd', '2131231231', '2026-10-10', '2026-10-10', '2026-10-11', 'Returned', NULL, 3, 12),
(11, 'Rayan Macapa-ar', 'Prk. Milestone brgy. San Miguel', '2131231231', '2026-10-10', '2026-10-10', '2026-10-11', 'Returned', NULL, 1, 4),
(12, 'rtyurtyrtyrt', 'rtyrtyrtyrty', '45345345345', '2026-10-10', '2026-10-10', '2026-10-16', 'Borrowed', NULL, 2, 2),
(13, 'ghnfgnfgbfgfgb', 'fgnfgnfgnfgnfgn', '23434534534534', '2026-10-10', '2026-10-17', '2026-10-31', 'Borrowed', NULL, 3, 20),
(14, 'asdasd', 'sadasdasdasd', '231231231231', '2026-10-12', '2026-10-12', '2026-10-14', 'Returned', NULL, 3, 10),
(15, 'clark', 'Prk,milestone dr, san miguel', '0909 392 3123', '2026-10-10', '2026-10-10', '2026-10-11', 'Approved', NULL, 3, 4);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(40) DEFAULT NULL,
  `category` varchar(40) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `availability` varchar(40) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `condition` varchar(40) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_name`, `category`, `quantity`, `availability`, `status`, `condition`, `qr_code`) VALUES
(1, 'Grass Cutter', 'Gardening', 4, 'Available', 'Active', 'Good', 'USER-83921-X7K4P'),
(2, 'Shovel', 'Gardening', 2, 'Borrowed', 'Active', 'Good', 'CTEBS-EQUIPMENT-2'),
(3, 'Chair', 'Furniture and Fixtures', 50, 'Available', 'Active', 'Good', 'CTEBS-EQUIPMENT-3');

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

CREATE TABLE `penalty` (
  `penalty_id` int(11) NOT NULL,
  `reason` varchar(40) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalty`
--

INSERT INTO `penalty` (`penalty_id`, `reason`, `amount`, `status`, `request_id`, `created_at`) VALUES
(3, 'Damaged Equipment', 999, 'Paid', 10, NULL),
(4, 'Damaged Equipment', 150, 'Paid', 8, NULL),
(5, 'Late Return', 500, 'Unpaid', 5, '2026-10-11 00:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(40) DEFAULT NULL,
  `generated_by` varchar(40) DEFAULT NULL,
  `date_generated` date DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `return_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_type`, `generated_by`, `date_generated`, `details`, `equipment_id`, `return_id`) VALUES
(1, 'Borrowing Transactions', '1', '2026-10-09', 'Borrowing Transactions Report\n\n', NULL, NULL),
(2, 'Equipment Inventory', '1', '2026-10-09', 'Equipment Inventory Report\n\nEquipment ID', NULL, NULL),
(3, 'Borrowing Transactions', '1', '2026-10-10', 'Borrowing Transactions Report\n\nRequest I', NULL, NULL),
(4, 'Borrowing Transactions', '1', '2026-10-10', 'Borrowing Transactions Report\n\nRequest I', NULL, NULL),
(5, 'Equipment Inventory', '1', '2026-10-10', 'Equipment Inventory Report\n\nEquipment ID', NULL, NULL),
(6, 'Penalty Records', '1', '2026-10-10', 'Penalty Records Report\n\nPenalty ID: 1\nRe', NULL, NULL),
(7, 'Return Records', '1', '2026-10-10', 'Return Records Report\n\nReturn ID: 1\nRequ', NULL, NULL),
(8, 'Penalty Records', '1', '2026-10-10', 'Penalty Records Report\n\nPenalty ID: 1\nRe', NULL, NULL),
(9, 'Return Records', '1', '2026-10-22', 'Return Records Report\n\nReturn ID: 2\nRequ', NULL, NULL),
(10, 'Borrowing Transactions', '1', '2026-10-13', 'Borrowing Transactions Report\n\nRequest I', NULL, NULL),
(11, 'Equipment Inventory', '1', '2026-10-10', 'Equipment Inventory Report\n\nEquipment ID', NULL, NULL),
(12, 'Equipment Inventory', '1', '2026-10-10', 'Equipment Inventory Report\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nQuantity: 50\nAvailability: Available\nCondition: Good\nStatus: Active\n-----------------------------\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nQuantity: 4\nAvailability: Borrowed\nCondition: Good\nStatus: Active\n-----------------------------\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nQuantity: 2\nAvailability: Borrowed\nCondition: Good\nStatus: Active\n-----------------------------\n', NULL, NULL),
(13, 'Equipment Inventory', '1', '2026-10-10', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 10, 2026 11:21 AM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 12\nAvailable Quantity: 38\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 4\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(14, 'Borrowing Transactions', '1', '2026-10-10', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 03:52 PM\n========================================\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity Requested: 20\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity Requested: 2\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity Requested: 4\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity Requested: 12\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity Requested: 13\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity Requested: 25\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity Requested: 1\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity Requested: 1\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity Requested: 1\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity Requested: 1\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(15, 'Return Records', '1', '2026-10-10', 'RETURN RECORDS REPORT\nDate Generated: October 10, 2026 03:52 PM\n========================================\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 12\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 13\nReturn Date: 2026-10-10\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 25\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 1\nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-22\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(16, 'Penalty Records', '1', '2026-10-10', 'PENALTY RECORDS REPORT\nDate Generated: October 10, 2026 03:52 PM\n========================================\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(17, 'Borrowing Transactions', '1', '2026-10-10', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 04:48 PM\nDate Range: Today\n========================================\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity: 20\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity: 2\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity: 12\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity: 13\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity: 25\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(18, 'Equipment Inventory', '1', '2026-10-10', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 10, 2026 04:48 PM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 20\nAvailable Quantity: 30\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 4\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(19, 'Penalty Records', '1', '2026-10-10', 'PENALTY RECORDS REPORT\nDate Generated: October 10, 2026 04:49 PM\n========================================\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(20, 'Return Records', '1', '2026-10-10', 'RETURN RECORDS REPORT\nDate Generated: October 10, 2026 04:49 PM\n========================================\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 12\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 13\nReturn Date: 2026-10-10\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 25\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 1\nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-22\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(21, 'Borrowing Transactions', '1', '2026-10-10', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 04:49 PM\nDate Range: All Transactions\n========================================\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity: 20\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity: 2\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity: 12\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity: 13\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity: 25\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(22, 'Borrowing Transactions', '1', '2026-10-12', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 12, 2026 04:51 PM\nDate Range: Today\n========================================\n\nRequest ID: #14\nBorrower: asdasd\nContact: 231231231231\nEquipment: Chair\nQuantity: 10\nDate Requested: 2026-10-12\nBorrow Date: 2026-10-12\nReturn Date: 2026-10-14\nStatus: Borrowed\n----------------------------------------\n\n', NULL, NULL),
(23, 'Borrowing Transactions', '1', '2026-10-10', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 04:52 PM\nDate Range: This Week\n========================================\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity: 20\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity: 2\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity: 12\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity: 13\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity: 25\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(24, 'Borrowing Transactions', '1', '2026-10-10', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 04:52 PM\nDate Range: This Month\n========================================\n\nRequest ID: #14\nBorrower: asdasd\nContact: 231231231231\nEquipment: Chair\nQuantity: 10\nDate Requested: 2026-10-12\nBorrow Date: 2026-10-12\nReturn Date: 2026-10-14\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity: 20\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity: 2\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity: 12\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity: 13\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity: 25\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(25, 'Equipment Inventory', '2', '2026-10-11', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 10, 2026 06:09 PM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 30\nAvailable Quantity: 20\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 0\nAvailable Quantity: 4\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(26, 'Equipment Inventory', '1', '2026-10-11', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 10, 2026 06:10 PM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 30\nAvailable Quantity: 20\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 0\nAvailable Quantity: 4\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(27, 'Borrowing Transactions', '2', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 10, 2026 06:11 PM\nDate Range: Today\n========================================\n\nNo borrowing requests found for this date range.\n', NULL, NULL),
(28, 'Return Records', '1', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:14 AM\n========================================\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nRequest Quantity: 4\nReturn Date: 2026-10-10\nReturn Condition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 12\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 13\nReturn Date: 2026-10-10\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nRequest Quantity: 25\nReturn Date: 2026-10-10\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nRequest Quantity: 1\nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nRequest Quantity: 1\nReturn Date: 2026-10-13\nReturn Condition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-22\nReturn Condition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: \nEquipment: \nRequest Quantity: \nReturn Date: 2026-10-10\nReturn Condition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(29, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:31 AM\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 4\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nQuantity: 12\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 13\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nQuantity: 25\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 1\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-22\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(30, 'Borrowing Transactions', '2', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 11, 2026 12:32 AM\nDate Range: All Transactions\n========================================\n\nRequest ID: #15\nBorrower: clark\nContact: 0909 392 3123\nEquipment: Chair\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Approved\n----------------------------------------\n\nRequest ID: #14\nBorrower: asdasd\nContact: 231231231231\nEquipment: Chair\nQuantity: 10\nDate Requested: 2026-10-12\nBorrow Date: 2026-10-12\nReturn Date: 2026-10-14\nStatus: Returned\n----------------------------------------\n\nRequest ID: #13\nBorrower: ghnfgnfgbfgfgb\nContact: 23434534534534\nEquipment: Chair\nQuantity: 20\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-17\nReturn Date: 2026-10-31\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #12\nBorrower: rtyurtyrtyrt\nContact: 45345345345\nEquipment: Shovel\nQuantity: 2\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-16\nStatus: Borrowed\n----------------------------------------\n\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nContact: 2131231231\nEquipment: Grass Cutter\nQuantity: 4\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #10\nBorrower: asdasd\nContact: 2131231231\nEquipment: Chair\nQuantity: 12\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nContact: 231231222\nEquipment: Chair\nQuantity: 13\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-12\nStatus: Returned\n----------------------------------------\n\nRequest ID: #8\nBorrower: asdasd\nContact: 23123122\nEquipment: Chair\nQuantity: 25\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-24\nReturn Date: 2026-10-31\nStatus: Returned\n----------------------------------------\n\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #6\nBorrower: asdasd\nContact: 09093045239\nEquipment: Shovel\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Grass Cutter\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Returned\n----------------------------------------\n\nRequest ID: #4\nBorrower: Rayan Macapa-ar\nContact: 09093045239\nEquipment: Chair\nQuantity: 1\nDate Requested: 2026-10-10\nBorrow Date: 2026-10-10\nReturn Date: 2026-10-11\nStatus: Rejected\n----------------------------------------\n\n', NULL, NULL),
(31, 'Borrowing Transactions', '2', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 11, 2026 12:32 AM\nDate Range: Today\n========================================\n\nNo borrowing requests found for this date range.\n', NULL, NULL),
(32, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:32 AM\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 4\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nQuantity: 12\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 13\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nQuantity: 25\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 1\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-22\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(33, 'Penalty Records', '2', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:32 AM\n========================================\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Unpaid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(34, 'Equipment Inventory', '1', '2026-10-11', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 11, 2026 12:43 AM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 20\nAvailable Quantity: 30\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 0\nAvailable Quantity: 4\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(35, 'Return Records', '1', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:44 AM\nDate Range: Today\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\n', NULL, NULL),
(36, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:44 AM\n========================================\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Unpaid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(37, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:45 AM\n========================================\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Paid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(38, 'Equipment Inventory', '1', '2026-10-11', 'EQUIPMENT INVENTORY REPORT\nDate Generated: October 11, 2026 12:45 AM\n========================================\n\nEquipment ID: 3\nEquipment Name: Chair\nCategory: Furniture and Fixtures\nTotal Stock: 50\nBorrowed Quantity: 20\nAvailable Quantity: 30\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 1\nEquipment Name: Grass Cutter\nCategory: Gardening\nTotal Stock: 4\nBorrowed Quantity: 0\nAvailable Quantity: 4\nCondition: Good\nStatus: Active\n----------------------------------------\n\nEquipment ID: 2\nEquipment Name: Shovel\nCategory: Gardening\nTotal Stock: 2\nBorrowed Quantity: 2\nAvailable Quantity: 0\nCondition: Good\nStatus: Active\n----------------------------------------\n\n', NULL, NULL),
(39, 'Return Records', '1', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:45 AM\nDate Range: Today\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\n', NULL, NULL),
(40, 'Borrowing Transactions', '1', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 11, 2026 12:45 AM\nDate Range: Today\n========================================\n\nNo borrowing requests found for the selected date range.\n', NULL, NULL),
(41, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:45 AM\n========================================\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Paid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Unpaid\n----------------------------------------\n\n', NULL, NULL),
(42, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:48 AM\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 4\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nQuantity: 12\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 13\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nQuantity: 25\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 1\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-22\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(43, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:54 AM\nDate Range: Today\n========================================\n\nNo penalty records found for the selected date range.\n', NULL, NULL),
(44, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:54 AM\nDate Range: Today\n========================================\n\nNo penalty records found for the selected date range.\n', NULL, NULL),
(45, 'Penalty Records', '1', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:54 AM\nDate Range: Today\n========================================\n\nPenalty ID: #5\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nBorrowed Quantity: 1\nReason: Late Return\nAmount: PHP 500.00\nStatus: Unpaid\nPenalty Created: 2026-10-11 00:54:41\n----------------------------------------\n\n', NULL, NULL),
(46, 'Return Records', '1', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 12:56 AM\nDate Range: Today\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\n', NULL, NULL),
(47, 'Borrowing Transactions', '1', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 11, 2026 12:56 AM\nDate Range: Today\n========================================\n\nNo borrowing requests found for the selected date range.\n', NULL, NULL),
(48, 'Penalty Records', '2', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 12:58 AM\n========================================\n\nPenalty ID: #5\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nBorrowed Quantity: 1\nReason: Late Return\nAmount: PHP 500.00\nStatus: Unpaid\n----------------------------------------\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Paid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Paid\n----------------------------------------\n\n', NULL, NULL),
(49, 'Penalty Records', '2', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 01:01 AM\n========================================\n\nPenalty ID: #5\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nBorrowed Quantity: 1\nReason: Late Return\nAmount: PHP 500.00\nStatus: Unpaid\n----------------------------------------\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Paid\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Paid\n----------------------------------------\n\n', NULL, NULL),
(50, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 01:01 AM\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 4\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nQuantity: 12\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 13\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nQuantity: 25\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 1\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-22\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(51, 'Borrowing Transactions', '2', '2026-10-11', 'BORROWING TRANSACTIONS REPORT\nDate Generated: October 11, 2026 01:01 AM\nDate Range: Today\n========================================\n\nNo borrowing requests found for this date range.\n', NULL, NULL),
(52, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 01:05 AM\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\nReturn ID: #9\nRequest ID: #11\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 4\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: All goods\n----------------------------------------\n\nReturn ID: #8\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nQuantity: 12\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: 2 of them are missing\n----------------------------------------\n\nReturn ID: #7\nRequest ID: #9\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 13\nReturn Date: 2026-10-10\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #6\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nQuantity: 25\nReturn Date: 2026-10-10\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #5\nRequest ID: #7\nBorrower: Rayan Macapa-ar\nEquipment: Chair\nQuantity: 1\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: None\n----------------------------------------\n\nReturn ID: #4\nRequest ID: #6\nBorrower: asdasd\nEquipment: Shovel\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: None\n----------------------------------------\n\nReturn ID: #3\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nQuantity: 1\nReturn Date: 2026-10-13\nCondition: Good\nRemarks: dasda\n----------------------------------------\n\nReturn ID: #2\nRequest ID: #3\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-22\nCondition: Damaged\nRemarks: None\n----------------------------------------\n\nReturn ID: #1\nRequest ID: #1\nBorrower: Unknown\nEquipment: Unknown\nQuantity: 0\nReturn Date: 2026-10-10\nCondition: Fair\nRemarks: Penalty\n----------------------------------------\n\n', NULL, NULL),
(53, 'Penalty Records', '2', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 01:07 AM\nDate Range: \n========================================\n\nPenalty ID: #5\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nBorrowed Quantity: 1\nReason: Late Return\nAmount: PHP 500.00\nStatus: Unpaid\nPenalty Created: 2026-10-11 00:54:41\n----------------------------------------\n\nPenalty ID: #4\nRequest ID: #8\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 25\nReason: Damaged Equipment\nAmount: PHP 150.00\nStatus: Paid\nPenalty Created: Date unavailable\n----------------------------------------\n\nPenalty ID: #3\nRequest ID: #10\nBorrower: asdasd\nEquipment: Chair\nBorrowed Quantity: 12\nReason: Damaged Equipment\nAmount: PHP 999.00\nStatus: Paid\nPenalty Created: Date unavailable\n----------------------------------------\n\n', NULL, NULL),
(54, 'Penalty Records', '2', '2026-10-11', 'PENALTY RECORDS REPORT\nDate Generated: October 11, 2026 01:11 AM\nDate Range: Today\n========================================\n\nPenalty ID: #5\nRequest ID: #5\nBorrower: Rayan Macapa-ar\nEquipment: Grass Cutter\nBorrowed Quantity: 1\nReason: Late Return\nAmount: PHP 500.00\nStatus: Unpaid\nPenalty Created: 2026-10-11 00:54:41\n----------------------------------------\n\n', NULL, NULL),
(55, 'Return Records', '2', '2026-10-11', 'RETURN RECORDS REPORT\nDate Generated: October 11, 2026 01:12 AM\nDate Range: Today\n========================================\n\nReturn ID: #10\nRequest ID: #14\nBorrower: asdasd\nEquipment: Chair\nQuantity: 10\nReturn Date: 2026-10-11\nCondition: Damaged\nRemarks: 5 of them was damaged!\n----------------------------------------\n\n', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `return_record`
--

CREATE TABLE `return_record` (
  `return_id` int(11) NOT NULL,
  `return_date` date DEFAULT NULL,
  `return_condition` varchar(40) DEFAULT NULL,
  `remarks` varchar(40) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_record`
--

INSERT INTO `return_record` (`return_id`, `return_date`, `return_condition`, `remarks`, `request_id`) VALUES
(1, '2026-10-10', 'Fair', 'Penalty', 1),
(2, '2026-10-22', 'Damaged', '', 3),
(3, '2026-10-13', 'Good', 'dasda', 5),
(4, '2026-10-13', 'Good', '', 6),
(5, '2026-10-10', 'Fair', '', 7),
(6, '2026-10-10', 'Damaged', '', 8),
(7, '2026-10-10', 'Good', '', 9),
(8, '2026-10-10', 'Damaged', '2 of them are missing', 10),
(9, '2026-10-10', 'Good', 'All goods', 11),
(10, '2026-10-11', 'Damaged', '5 of them was damaged!', 14);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `role` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `address`, `username`, `password`, `role`) VALUES
(1, 'Joel Jumawan', 'San Migue, Iligan City', 'admin', 'joel1234', 'Captain'),
(2, 'Rayan Macapa-ar', 'San Miguel, Iligan City', 'Seoyan', 'rayan123', 'Personnel');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrower_request`
--
ALTER TABLE `borrower_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `equipment_borrower_request` (`equipment_id`),
  ADD KEY `user_borrower_request` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `penalty`
--
ALTER TABLE `penalty`
  ADD PRIMARY KEY (`penalty_id`),
  ADD KEY `borrower_request_penalty` (`request_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `equipment_reports` (`equipment_id`),
  ADD KEY `return_record_reports` (`return_id`);

--
-- Indexes for table `return_record`
--
ALTER TABLE `return_record`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `borrower_request_return_record` (`request_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrower_request`
--
ALTER TABLE `borrower_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `return_record`
--
ALTER TABLE `return_record`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrower_request`
--
ALTER TABLE `borrower_request`
  ADD CONSTRAINT `equipment_borrower_request` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`),
  ADD CONSTRAINT `user_borrower_request` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `penalty`
--
ALTER TABLE `penalty`
  ADD CONSTRAINT `borrower_request_penalty` FOREIGN KEY (`request_id`) REFERENCES `borrower_request` (`request_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `equipment_reports` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`),
  ADD CONSTRAINT `return_record_reports` FOREIGN KEY (`return_id`) REFERENCES `return_record` (`return_id`);

--
-- Constraints for table `return_record`
--
ALTER TABLE `return_record`
  ADD CONSTRAINT `borrower_request_return_record` FOREIGN KEY (`request_id`) REFERENCES `borrower_request` (`request_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
