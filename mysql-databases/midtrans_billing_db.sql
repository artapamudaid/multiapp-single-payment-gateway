-- Adminer 4.17.1 MySQL 10.11.14-MariaDB-0ubuntu0.24.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

CREATE TABLE `apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(50) NOT NULL,
  `app_key` varchar(100) NOT NULL,
  `app_secret` varchar(255) NOT NULL,
  `webhook_url` varchar(255) NOT NULL,
  `app_status` enum('active','suspended') DEFAULT 'active',
  `plan_name` varchar(100) DEFAULT NULL,
  `amount` int(11) DEFAULT 0,
  `sub_status` enum('inactive','active','cancelled') DEFAULT 'inactive',
  `activation_date` datetime DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `midtrans_sub_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_name` (`app_name`),
  UNIQUE KEY `app_key` (`app_key`),
  UNIQUE KEY `midtrans_sub_id` (`midtrans_sub_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(50) NOT NULL,
  `midtrans_transaction_id` varchar(255) DEFAULT NULL,
  `plan_name` varchar(100) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `billing_period` varchar(7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `app_name` (`app_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `duration_months` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(50) NOT NULL,
  `midtrans_sub_id` varchar(255) DEFAULT NULL,
  `plan_name` varchar(100) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `sub_status` enum('active','failed','cancelled') DEFAULT 'active',
  `next_billing_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `midtrans_sub_id` (`midtrans_sub_id`),
  KEY `app_name` (`app_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2026-10-09 04:14:45
