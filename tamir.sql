-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for repair_shop
CREATE DATABASE IF NOT EXISTS `repair_shop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `repair_shop`;

-- Dumping structure for table repair_shop.activity_log
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, update, delete, login, logout',
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'repair, customer, part, user, ...',
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.activity_log: ~52 rows (approximately)
DELETE FROM `activity_log`;
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `created_at`) VALUES
	(1, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:46:28'),
	(2, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:46:33'),
	(3, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:46:37'),
	(4, 2, 'update', 'user', 2, 'ویرایش پروفایل و تغییر رمز عبور', '2026-05-21 08:47:41'),
	(5, 2, 'create', 'user', 3, 'ایجاد کاربر a.dehghani', '2026-05-21 08:50:40'),
	(6, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:50:45'),
	(7, 3, 'login', 'user', 3, 'ورود به سیستم', '2026-05-21 08:50:51'),
	(8, 3, 'logout', 'user', 3, 'خروج از سیستم', '2026-05-21 08:51:10'),
	(9, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:51:16'),
	(10, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:51:25'),
	(11, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:51:27'),
	(12, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:52:22'),
	(13, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:52:24'),
	(14, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:52:38'),
	(15, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:52:44'),
	(16, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:53:16'),
	(17, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:53:16'),
	(18, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:55:47'),
	(19, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:55:53'),
	(20, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:55:58'),
	(21, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:55:59'),
	(22, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:56:13'),
	(23, 2, 'update', 'user', 3, 'تغییر وضعیت کاربر', '2026-05-21 08:56:17'),
	(24, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:56:47'),
	(25, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:58:41'),
	(26, 2, 'logout', 'user', 2, 'خروج از سیستم', '2026-05-21 08:59:27'),
	(27, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 08:59:39'),
	(28, 2, 'update', 'part', 3, 'ویرایش قطعه 9999', '2026-05-21 09:17:07'),
	(29, 2, 'update', 'part', 2, 'ویرایش قطعه تست 2', '2026-05-21 09:17:49'),
	(30, 2, 'update', 'part', 4, 'ویرایش قطعه تست 20', '2026-05-21 09:18:13'),
	(31, 2, 'create', 'customer', 3, 'افزودن مشتری احسان دهقانی 1', '2026-05-21 09:25:56'),
	(32, 2, 'create', 'device_type', 4, 'افزودن نوع دستگاه دستگاه جدید 1', '2026-05-21 09:26:08'),
	(33, 2, 'create', 'technician', 2, 'افزودن تکنسین تکنسین بدون وجود خارجی', '2026-05-21 09:27:25'),
	(34, 2, 'create', 'technician', 3, 'افزودن تکنسین تکنسین بدون وجود خارجی', '2026-05-21 09:27:25'),
	(35, 2, 'create', 'part', 9, 'افزودن قطعه آی سی تاچ', '2026-05-21 09:28:12'),
	(36, 2, 'create', 'repair', 6, 'ایجاد سرویس با فاکتور FA-1405-0005', '2026-05-21 09:29:01'),
	(37, 2, 'delete', 'repair', 1, '{"id":1,"customer_id":2,"device_type_id":1,"device_brand":"سیماران","device_model":"1329","device_serial":"123231231","device_year":1402,"device_location":"آزادشهر - کوچه توحید 8 پلاک 32","service_type_id":1,"problem_part":"خرابی میکروفون درب ورودی","fault_description":"2222","solution_description":"","technician_id":1,"service_date":"2026-02-24","service_time":"00:00:00","next_service_date":"2026-04-04","labor_cost":12200000,"extra_costs":0,"payment_status":"paid","invoice_number":"FA-1405-0001","final_status":"","technician_notes":"","warranty_months":0,"created_at":"2026-04-25 00:11:49","created_by":null,"updated_by":null}', '2026-05-21 09:51:56'),
	(38, 2, 'delete', 'repair', 3, '{"id":3,"customer_id":1,"device_type_id":null,"device_brand":"سیماران","device_model":"1329","device_serial":"4444444","device_year":5555,"device_location":"آزادشهر - کوچه توحید 8 پلاک 32","service_type_id":1,"problem_part":"خرابی میکروفون درب ورودی","fault_description":"سی","solution_description":"شش","technician_id":1,"service_date":"2026-04-17","service_time":"17:21:00","next_service_date":"2026-06-03","labor_cost":2000000,"extra_costs":2100000,"payment_status":"paid","invoice_number":"FA-1405-0002","final_status":"شسی","technician_notes":"شسیشس","warranty_months":3,"created_at":"2026-04-25 03:20:06","created_by":null,"updated_by":null}', '2026-05-21 10:24:10'),
	(39, 2, 'update', 'repair', 6, '{"id":6,"customer_id":3,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-20","service_time":"13:02:00","next_service_date":"2026-06-05","labor_cost":50000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2,"customer_name":"احسان دهقانی 1","customer_phone":"09140911941","technician_name":"تکنسین بدون وجود خارجی","device_type_name":"دستگاه جدید 1","service_type_name":"تعمیر"}', '2026-05-21 10:35:48'),
	(40, 2, 'update', 'repair', 6, '{"id":6,"customer_id":2,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-19","service_time":"13:02:00","next_service_date":"2026-06-04","labor_cost":50000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:38:41'),
	(41, 2, 'update', 'repair', 6, '{"id":6,"customer_id":2,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-19","service_time":"13:02:00","next_service_date":"2026-06-04","labor_cost":50000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:38:59'),
	(42, 2, 'update', 'repair', 6, '{"id":6,"customer_id":2,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":2,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-18","service_time":"13:02:00","next_service_date":"2026-06-03","labor_cost":50000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:42:40'),
	(43, 2, 'update', 'repair', 6, '{"id":6,"customer_id":2,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-18","service_time":"13:02:00","next_service_date":"2026-06-03","labor_cost":40000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:42:47'),
	(44, 2, 'create', 'device_type', 5, 'افزودن نوع دستگاه samsung A56', '2026-05-21 10:43:06'),
	(45, 2, 'update', 'repair', 6, '{"id":6,"customer_id":3,"device_type_id":4,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-17","service_time":"13:02:00","next_service_date":"2026-06-02","labor_cost":40000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:43:08'),
	(46, 2, 'update', 'repair', 6, '{"id":6,"customer_id":3,"device_type_id":5,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-16","service_time":"13:02:00","next_service_date":"2026-06-01","labor_cost":40000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 10:43:27'),
	(47, 2, 'update', 'repair', 3, 'بازگردانی به نسخهٔ قبلی', '2026-05-21 10:43:44'),
	(48, 2, 'update', 'repair', 1, 'بازگردانی به نسخهٔ قبلی', '2026-05-21 10:44:21'),
	(49, 2, 'update', 'repair', 3, 'بازگردانی به نسخهٔ قبلی', '2026-05-21 10:48:07'),
	(50, 2, 'update', 'repair', 5, '{"id":5,"customer_id":2,"device_type_id":1,"device_brand":"تست","device_model":"۱۳۹۰","device_serial":"۸۲۸","device_year":1399,"device_location":"یزد نمیدانم","service_type_id":2,"problem_part":"تست","fault_description":"نمیدانم 222","solution_description":"صلاح نمیدانم  66666   444","technician_id":1,"service_date":"2025-11-23","service_time":"05:36:00","next_service_date":"2026-06-10","labor_cost":2000000,"extra_costs":6000000,"payment_status":"unpaid","invoice_number":"FA-1405-0004","final_status":"تست نشد","technician_notes":"الکیه","warranty_months":36,"created_at":"2026-05-21 00:58:35","created_by":null,"updated_by":null}', '2026-05-21 15:02:41'),
	(51, 2, 'create', 'customer', 4, 'افزودن مشتری احسان دهقانی 3', '2026-05-21 15:36:40'),
	(52, 2, 'update', 'customer', 1, 'ویرایش مشتری علی اصغر دهقانی', '2026-05-21 15:45:58'),
	(53, 2, 'create', 'repair', 7, 'ایجاد سرویس با فاکتور FA-1405-0006', '2026-05-21 15:48:04'),
	(54, 2, 'update', 'repair', 6, '{"id":6,"customer_id":3,"device_type_id":5,"device_brand":"اپل","device_model":"1405","device_serial":"12358574721","device_year":1405,"device_location":"یزد کوچه تست ها","service_type_id":1,"problem_part":"آی سی تاچ","fault_description":"آی تاچ قطع شده است","solution_description":"تعویض آی سی تاچ","technician_id":2,"service_date":"2026-05-16","service_time":"13:02:00","next_service_date":"2026-06-01","labor_cost":40000000,"extra_costs":0,"payment_status":"partial","invoice_number":"FA-1405-0005","final_status":"تست شد و کاملا تاچ کار میکرد.","technician_notes":"سه ماه گارانتی فقط بابت آی سی تاچ","warranty_months":3,"created_at":"2026-05-21 12:59:01","created_by":2,"updated_by":2}', '2026-05-21 15:56:12'),
	(55, 2, 'create', 'transaction', 1, 'دریافت از مشتری', '2026-05-21 16:08:12'),
	(56, 2, 'create', 'supplier', 1, 'افزودن تأمین‌کننده نور پخش', '2026-05-21 16:18:05'),
	(57, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-21 19:21:15'),
	(58, 2, 'login', 'user', 2, 'ورود به سیستم', '2026-05-24 19:35:31'),
	(59, 2, 'create', 'part', 10, 'افزودن قطعه ooo', '2026-05-24 19:37:21'),
	(60, 2, 'create', 'repair', 8, 'ایجاد سرویس با فاکتور FA-1405-0007', '2026-05-24 19:38:13'),
	(61, 2, 'create', 'transaction', 2, 'دریافت از مشتری', '2026-05-24 19:41:21');

-- Dumping structure for table repair_shop.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `customer_type` enum('personal','company') DEFAULT 'personal',
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.customers: ~2 rows (approximately)
DELETE FROM `customers`;
INSERT INTO `customers` (`id`, `name`, `phone`, `phone2`, `address`, `customer_type`, `company_name`, `email`, `notes`) VALUES
	(1, 'علی اصغر دهقانی', '09133530345', '03535221545', 'تست آدرس', 'personal', '', '123@123.com', 'تست توضیحات 123.com'),
	(2, 'علی اصغر دهقانی 2', '0912345555', NULL, 'آدرس نداریم', 'personal', NULL, '4444@', NULL),
	(3, 'احسان دهقانی 1', '09140911941', NULL, 'پردیس', 'personal', NULL, 'edsanig@gmail.com', NULL),
	(4, 'احسان دهقانی 3', '091409119411', '02121002585', 'تهران پاسداران میدان حسین آباد', 'company', 'روغن موتور پردیس', 'info@pardismotoroil.com', 'تست توضیحات شرکت روغن موتور پردیس');

-- Dumping structure for table repair_shop.device_types
CREATE TABLE IF NOT EXISTS `device_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.device_types: ~5 rows (approximately)
DELETE FROM `device_types`;
INSERT INTO `device_types` (`id`, `name`) VALUES
	(1, 'آیفون'),
	(2, 'جک فک'),
	(3, 'سیللس'),
	(4, 'دستگاه جدید 1'),
	(5, 'samsung A56');

-- Dumping structure for table repair_shop.menu_items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.menu_items: ~13 rows (approximately)
DELETE FROM `menu_items`;
INSERT INTO `menu_items` (`id`, `title`, `url`, `sort_order`, `parent_id`, `icon`, `is_active`) VALUES
	(19, '🏠 داشبورد', 'index.php', 1, NULL, NULL, 1),
	(20, '➕ ثبت سرویس جدید', '#', 2, NULL, NULL, 1),
	(21, '📋 لیست سرویس‌ها', '#', 3, NULL, NULL, 1),
	(22, '📝 ثبت سرویس', 'create.php', 1, 20, NULL, 1),
	(23, '👤 مشتری جدید', 'customers.php?add=1', 2, 20, NULL, 1),
	(24, '⚙️ قطعه جدید', 'parts.php?add=1', 3, 20, NULL, 1),
	(25, '🏭 تأمین‌کننده جدید', 'suppliers.php?add=1', 4, 20, NULL, 1),
	(26, '📄 لیست سرویس‌ها', 'reports.php', 1, 21, NULL, 1),
	(27, '👥 مشتریان', 'customers.php', 2, 21, NULL, 1),
	(28, '⚙️ قطعات', 'parts.php', 3, 21, NULL, 1),
	(29, '🏭 تأمین‌کنندگان', 'suppliers.php', 4, 21, NULL, 1),
	(30, '📊 گزارشات', 'financial_reports.php', 5, 21, NULL, 1),
	(31, '💰 تراکنش‌ها', 'transactions.php', 6, 21, NULL, 1);

-- Dumping structure for table repair_shop.parts
CREATE TABLE IF NOT EXISTS `parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `default_purchase_price` bigint(20) DEFAULT NULL,
  `default_sale_price` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.parts: ~9 rows (approximately)
DELETE FROM `parts`;
INSERT INTO `parts` (`id`, `name`, `stock`, `default_purchase_price`, `default_sale_price`) VALUES
	(1, 'هیچی تسته1', 0, NULL, NULL),
	(2, 'تست 2', 10, 3000, 5000),
	(3, '9999', 3, 2000, 10000),
	(4, 'تست 20', 20, 50000, 70000),
	(5, 'مسشش', 0, NULL, NULL),
	(6, 'هیچی تسته1', 0, NULL, NULL),
	(7, 'ششش @', 0, NULL, NULL),
	(8, 'جک فک', 0, NULL, NULL),
	(9, 'آی سی تاچ', -1, NULL, NULL),
	(10, 'ooo', -1, NULL, NULL);

-- Dumping structure for table repair_shop.purchases
CREATE TABLE IF NOT EXISTS `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `total_amount` bigint(20) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.purchases: ~0 rows (approximately)
DELETE FROM `purchases`;

-- Dumping structure for table repair_shop.purchase_items
CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `part_id` (`part_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.purchase_items: ~0 rows (approximately)
DELETE FROM `purchase_items`;

-- Dumping structure for table repair_shop.repairs
CREATE TABLE IF NOT EXISTS `repairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `device_type_id` int(11) DEFAULT NULL,
  `device_brand` varchar(50) DEFAULT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `device_serial` varchar(100) DEFAULT NULL,
  `device_year` int(11) DEFAULT NULL,
  `device_location` varchar(255) DEFAULT NULL,
  `service_type_id` int(11) DEFAULT NULL,
  `problem_part` varchar(100) DEFAULT NULL,
  `fault_description` text DEFAULT NULL,
  `solution_description` text DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `service_time` time DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `labor_cost` bigint(20) DEFAULT 0,
  `extra_costs` bigint(20) DEFAULT 0,
  `payment_status` enum('paid','unpaid','partial') DEFAULT 'unpaid',
  `invoice_number` varchar(20) DEFAULT NULL,
  `final_status` varchar(50) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `warranty_months` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `customer_id` (`customer_id`),
  KEY `device_type_id` (`device_type_id`),
  KEY `service_type_id` (`service_type_id`),
  KEY `technician_id` (`technician_id`),
  KEY `repairs_created_by_fk` (`created_by`),
  KEY `repairs_updated_by_fk` (`updated_by`),
  CONSTRAINT `repairs_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repairs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repairs_ibfk_2` FOREIGN KEY (`device_type_id`) REFERENCES `device_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repairs_ibfk_3` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repairs_ibfk_4` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repairs_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.repairs: ~5 rows (approximately)
DELETE FROM `repairs`;
INSERT INTO `repairs` (`id`, `customer_id`, `device_type_id`, `device_brand`, `device_model`, `device_serial`, `device_year`, `device_location`, `service_type_id`, `problem_part`, `fault_description`, `solution_description`, `technician_id`, `service_date`, `service_time`, `next_service_date`, `labor_cost`, `extra_costs`, `payment_status`, `invoice_number`, `final_status`, `technician_notes`, `warranty_months`, `created_at`, `created_by`, `updated_by`) VALUES
	(1, 2, 1, 'سیماران', '1329', '123231231', 1402, 'آزادشهر - کوچه توحید 8 پلاک 32', 1, 'خرابی میکروفون درب ورودی', '2222', '', 1, '2026-02-24', '00:00:00', '2026-04-04', 12200000, 0, 'paid', 'FA-1405-0001', '', '', 0, '2026-05-21 10:44:21', NULL, NULL),
	(3, 1, NULL, 'سیماران', '1329', '4444444', 5555, 'آزادشهر - کوچه توحید 8 پلاک 32', 1, 'خرابی میکروفون درب ورودی', 'سی', 'شش', 1, '2026-04-17', '17:21:00', '2026-06-03', 2000000, 2100000, 'paid', 'FA-1405-0002', 'شسی', 'شسیشس', 3, '2026-05-21 10:48:07', NULL, NULL),
	(4, 1, 1, 'موبایل 0011', '8923', '678123422', 1405, 'تهران تهران ', 2, 'مشکل داشت', 'شسمیشس', '', 1, '2026-02-21', '11:50:00', '2026-05-24', 0, 0, 'unpaid', 'FA-1405-0003', '', '', 0, '2026-05-01 16:16:01', NULL, NULL),
	(5, 2, 1, 'تست', '۱۳۹۰', '۸۲۸', 1399, 'یزد نمیدانم', 2, 'تست', 'نمیدانم 222', 'صلاح نمیدانم  66666   444', 1, '2025-11-22', '05:36:00', '2026-06-09', 2000000, 6000000, 'unpaid', 'FA-1405-0004', 'تست نشد', 'الکیه', 36, '2026-05-20 21:28:35', NULL, 2),
	(6, 3, 5, 'اپل', '1406', '12358574721', 1405, 'یزد کوچه تست ها', 1, 'آی سی تاچ', 'آی تاچ قطع شده است', 'تعویض آی سی تاچ', 2, '2026-05-15', '13:02:00', '2026-05-31', 40000000, 0, 'partial', 'FA-1405-0005', 'تست شد و کاملا تاچ کار میکرد.', 'سه ماه گارانتی فقط بابت آی سی تاچ', 3, '2026-05-21 09:29:01', 2, 2),
	(7, 4, 5, 'samsung', 'A12', '187923612846', 1405, 'تهران منزل خودم', 1, 'نداشته تازه راه اندازی کردم', 'تست', 'تستس', 1, '2026-05-21', '13:02:00', '2026-06-21', 0, 0, 'unpaid', 'FA-1405-0006', '', '', 0, '2026-05-21 15:48:04', 2, 2),
	(8, 4, 5, '', '', '', 0, '', 1, '', 'oooouuuu', '', 2, NULL, '00:00:00', NULL, 900000, 0, 'paid', 'FA-1405-0007', '', '', 0, '2026-05-24 19:38:13', 2, 2);

-- Dumping structure for table repair_shop.repair_expenses
CREATE TABLE IF NOT EXISTS `repair_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `amount` bigint(20) DEFAULT 0,
  `expense_type` enum('customer_charge','internal_cost') DEFAULT 'internal_cost',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `repair_id` (`repair_id`),
  CONSTRAINT `repair_expenses_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table repair_shop.repair_expenses: ~0 rows (approximately)
DELETE FROM `repair_expenses`;

-- Dumping structure for table repair_shop.repair_parts
CREATE TABLE IF NOT EXISTS `repair_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` bigint(20) DEFAULT 0,
  `purchase_price` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `repair_id` (`repair_id`),
  KEY `part_id` (`part_id`),
  CONSTRAINT `repair_parts_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_parts_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.repair_parts: ~2 rows (approximately)
DELETE FROM `repair_parts`;
INSERT INTO `repair_parts` (`id`, `repair_id`, `part_id`, `quantity`, `unit_price`, `purchase_price`) VALUES
	(68, 5, 8, 1, 2000000, 102000),
	(69, 6, 9, 1, 20000000, 18000000),
	(70, 8, 10, 1, 80000, 90000);

-- Dumping structure for table repair_shop.service_types
CREATE TABLE IF NOT EXISTS `service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.service_types: ~2 rows (approximately)
DELETE FROM `service_types`;
INSERT INTO `service_types` (`id`, `name`) VALUES
	(1, 'تعمیر'),
	(2, 'تعویض');

-- Dumping structure for table repair_shop.suppliers
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.suppliers: ~0 rows (approximately)
DELETE FROM `suppliers`;
INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`, `email`, `notes`, `created_at`) VALUES
	(1, 'نور پخش', '09130912092', NULL, NULL, NULL, '2026-05-21 16:18:05');

-- Dumping structure for table repair_shop.technicians
CREATE TABLE IF NOT EXISTS `technicians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.technicians: ~3 rows (approximately)
DELETE FROM `technicians`;
INSERT INTO `technicians` (`id`, `name`, `phone`) VALUES
	(1, 'علی اصغر دهقانی', ''),
	(2, 'تکنسین بدون وجود خارجی', ''),
	(3, 'تکنسین بدون وجود خارجی', '');

-- Dumping structure for table repair_shop.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('income','expense') NOT NULL,
  `amount` bigint(20) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `related_repair_id` int(11) DEFAULT NULL,
  `related_purchase_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `related_repair_id` (`related_repair_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`related_repair_id`) REFERENCES `repairs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.transactions: ~0 rows (approximately)
DELETE FROM `transactions`;
INSERT INTO `transactions` (`id`, `type`, `amount`, `description`, `date`, `customer_id`, `supplier_id`, `related_repair_id`, `related_purchase_id`, `created_at`) VALUES
	(1, 'income', 2000000, 'تست', '0000-00-00', 3, NULL, 6, NULL, '2026-05-21 16:08:12'),
	(2, 'income', 980000, '', '1405-03-03', 4, NULL, 8, NULL, '2026-05-24 19:41:21');

-- Dumping structure for table repair_shop.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table repair_shop.users: ~2 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
	(2, 'admin', '$2y$10$SI2FUvoTnQr2wKMHseEQaetfbQw04/wLPALq90NCjksFmH7xQGU2O', 'مدیر سیستم - احسان دهقانی', 'edsanig@gmail.com', 'super_admin', 1, '2026-05-21 08:46:19', '2026-05-21 08:47:41'),
	(3, 'a.dehghani', '$2y$10$vSyGRoqclKk6Z5MBevNNw.UEPE0O24wI2I2/u1e7pmBiNPVehwYJi', 'علی اصغر دهقانی', NULL, 'admin', 1, '2026-05-21 08:50:40', '2026-05-21 08:56:17');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
