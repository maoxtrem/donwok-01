# ************************************************************
# Antares - SQL Client
# Version 0.7.14
# 
# https://antares-sql.app/
# https://github.com/antares-sql/antares
# 
# Host: 127.0.0.1 (MySQL Community Server - GPL 8.4.13)
# Database: app
# Generation time: 2026-10-25T07:36:31-05:00
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table cash_boxes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cash_boxes`;

CREATE TABLE `cash_boxes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `cashier_id` int DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `uuid` varchar(36) NOT NULL,
  `defaultPaymentMethod` varchar(20) DEFAULT NULL,
  `moneyType` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_FECF29C5D17F50A6` (`uuid`),
  KEY `IDX_FECF29C52EDB0489` (`cashier_id`),
  CONSTRAINT `FK_FECF29C52EDB0489` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `cash_boxes` WRITE;
/*!40000 ALTER TABLE `cash_boxes` DISABLE KEYS */;

INSERT INTO `cash_boxes` (`id`, `name`, `createdAt`, `updatedAt`, `cashier_id`, `balance`, `uuid`, `defaultPaymentMethod`, `moneyType`) VALUES
	(2, "Nequi", "2026-10-25 08:32:17", "2026-10-25 11:06:30", 2, 0, "4fa7d40f-622e-32c5-2d02-f7ceed5341af", "digital", "digital"),
	(3, "Caja menor", "2026-10-25 08:33:02", "2026-10-25 11:14:25", NULL, 50200, "d4ab7048-3c08-b4c5-2a66-995aa0854a11", NULL, "efectivo"),
	(4, "Banco", "2026-10-25 08:33:12", "2026-10-25 11:14:34", NULL, 5001.98, "3f5e5c08-c237-c1c0-0987-d06d59d018c7", NULL, "digital"),
	(5, "Caja mayor", "2026-10-25 08:33:19", "2026-10-25 08:33:19", NULL, 0, "bf9b283f-6830-ffac-706a-a1608c3d2cff", NULL, "efectivo"),
	(6, "Efectivo", "2026-10-25 08:43:14", "2026-10-25 11:06:30", 2, 0, "1dfbf1fa-d050-11f1-980a-e2c104d05810", "efectivo", "efectivo"),
	(7, "Digital", "2026-10-25 08:43:14", "2026-10-25 08:59:25", 2, 0, "1dfbf767-d050-11f1-980a-e2c104d05810", NULL, "digital");

/*!40000 ALTER TABLE `cash_boxes` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table cash_closure_lines
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cash_closure_lines`;

CREATE TABLE `cash_closure_lines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `closure_id` int NOT NULL,
  `cashBox_id` int NOT NULL,
  `expected` decimal(12,2) NOT NULL,
  `counted` decimal(12,2) NOT NULL,
  `difference` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2D94CE36FA43E9DA` (`closure_id`),
  KEY `IDX_2D94CE3666BD09B9` (`cashBox_id`),
  CONSTRAINT `FK_CASH_CLOSURE_LINE_BOX` FOREIGN KEY (`cashBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_CASH_CLOSURE_LINE_CLOSURE` FOREIGN KEY (`closure_id`) REFERENCES `cash_closures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `cash_closure_lines` WRITE;
/*!40000 ALTER TABLE `cash_closure_lines` DISABLE KEYS */;

INSERT INTO `cash_closure_lines` (`id`, `closure_id`, `cashBox_id`, `expected`, `counted`, `difference`) VALUES
	(1, 1, 2, 5001.98, 5001.98, 0),
	(2, 1, 6, 50200, 50200, 0),
	(3, 1, 7, 0, 0, 0);

/*!40000 ALTER TABLE `cash_closure_lines` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table cash_closures
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cash_closures`;

CREATE TABLE `cash_closures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashier_id` int NOT NULL,
  `closedBy_id` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expectedTotal` decimal(12,2) NOT NULL,
  `countedTotal` decimal(12,2) NOT NULL,
  `differenceTotal` decimal(12,2) NOT NULL,
  `observations` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8811DB092EDB0489` (`cashier_id`),
  KEY `IDX_8811DB092F80AB3E` (`closedBy_id`),
  KEY `IDX_8811DB093174800F` (`createdBy_id`),
  KEY `IDX_8811DB0965FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_CASH_CLOSURE_CASHIER` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_CASH_CLOSURE_CLOSED_BY` FOREIGN KEY (`closedBy_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_CASH_CLOSURE_CREATED_BY` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CASH_CLOSURE_UPDATED_BY` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `cash_closures` WRITE;
/*!40000 ALTER TABLE `cash_closures` DISABLE KEYS */;

INSERT INTO `cash_closures` (`id`, `cashier_id`, `closedBy_id`, `status`, `expectedTotal`, `countedTotal`, `differenceTotal`, `observations`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`) VALUES
	(1, 2, 5, "cerrado", 55201.98, 55201.98, 0, "", "2026-10-25 11:06:30", "2026-10-25 11:06:30", 5, 5);

/*!40000 ALTER TABLE `cash_closures` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table cash_ledger_entries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cash_ledger_entries`;

CREATE TABLE `cash_ledger_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashBox_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `movement_id` int DEFAULT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `receiptKey` varchar(255) DEFAULT NULL,
  `receiptOriginalName` varchar(255) DEFAULT NULL,
  `receiptMimeType` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F6001E866BD09B9` (`cashBox_id`),
  KEY `IDX_8F6001E88D9F6D38` (`order_id`),
  KEY `IDX_8F6001E8229E70A7` (`movement_id`),
  KEY `IDX_8F6001E83174800F` (`createdBy_id`),
  KEY `IDX_8F6001E865FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_1FC8A4A43174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1FC8A4A45B8D6C7F` FOREIGN KEY (`cashBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_1FC8A4A465FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1FC8A4A48D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1FC8A4A4A3F4A8D2` FOREIGN KEY (`movement_id`) REFERENCES `cash_movements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `cash_ledger_entries` WRITE;
/*!40000 ALTER TABLE `cash_ledger_entries` DISABLE KEYS */;

INSERT INTO `cash_ledger_entries` (`id`, `cashBox_id`, `order_id`, `movement_id`, `createdBy_id`, `updatedBy_id`, `type`, `amount`, `description`, `createdAt`, `updatedAt`, `receiptKey`, `receiptOriginalName`, `receiptMimeType`, `status`) VALUES
	(1, 6, 7, NULL, 5, 5, "venta", 8000, "Pago de orden #1", "2026-10-25 10:14:30", "2026-10-25 10:14:30", NULL, NULL, NULL, "validado"),
	(2, 2, 7, NULL, 5, 5, "venta", 10000, "Pago de orden #1", "2026-10-25 10:14:30", "2026-10-25 10:14:30", NULL, NULL, NULL, "validado"),
	(3, 6, 8, NULL, 5, 5, "venta", 42200, "Pago de orden #2", "2026-10-25 10:29:09", "2026-10-25 10:29:09", NULL, NULL, NULL, "validado"),
	(4, 2, NULL, NULL, 5, 5, "egreso", -4999.98, "pago", "2026-10-25 10:56:44", "2026-10-25 10:57:55", "cash-egress/2026-10-25/84f0fcfe021a1b09a4432051.jpg", "17929258514472940087041554497.jpg", "image/jpeg", "validado"),
	(5, 2, NULL, NULL, 5, 5, "cierre_salida", -5001.98, "Saldo trasladado a caja temporal por cierre", "2026-10-25 11:06:30", "2026-10-25 11:06:30", NULL, NULL, NULL, "validado"),
	(6, 6, NULL, NULL, 5, 5, "cierre_salida", -50200, "Saldo trasladado a caja temporal por cierre", "2026-10-25 11:06:30", "2026-10-25 11:06:30", NULL, NULL, NULL, "validado"),
	(7, 3, NULL, NULL, 1, 1, "traslado_entrada", 50200, "Transferencia desde caja temporal Temporal cierre #pendiente · Efectivo", "2026-10-25 11:14:25", "2026-10-25 11:14:25", NULL, NULL, NULL, "validado"),
	(8, 4, NULL, NULL, 1, 1, "traslado_entrada", 5001.98, "Transferencia desde caja temporal Temporal cierre #pendiente · Nequi", "2026-10-25 11:14:34", "2026-10-25 11:14:34", NULL, NULL, NULL, "validado");

/*!40000 ALTER TABLE `cash_ledger_entries` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table cash_movements
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cash_movements`;

CREATE TABLE `cash_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sourceBox_id` int NOT NULL,
  `destinationBox_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DD9448DDCC8B49AA` (`sourceBox_id`),
  KEY `IDX_DD9448DD4FE3326` (`destinationBox_id`),
  KEY `IDX_DD9448DD3174800F` (`createdBy_id`),
  KEY `IDX_DD9448DD65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_4A0D4A5F1F1B9B5E` FOREIGN KEY (`destinationBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_4A0D4A5F3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4A0D4A5F5B8D6C7F` FOREIGN KEY (`sourceBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_4A0D4A5F65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;





# Dump of table cashiers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cashiers`;

CREATE TABLE `cashiers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `user_id` int DEFAULT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `name` varchar(120) NOT NULL DEFAULT 'Cajero',
  `uuid` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_EE44F633D17F50A6` (`uuid`),
  UNIQUE KEY `UNIQ_EE44F633A76ED395` (`user_id`),
  KEY `IDX_EE44F6333174800F` (`createdBy_id`),
  KEY `IDX_EE44F63365FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_EE44F6333174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_EE44F63365FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_EE44F633A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `cashiers` WRITE;
/*!40000 ALTER TABLE `cashiers` DISABLE KEYS */;

INSERT INTO `cashiers` (`id`, `active`, `createdAt`, `updatedAt`, `user_id`, `createdBy_id`, `updatedBy_id`, `name`, `uuid`) VALUES
	(2, 1, "2026-10-25 08:27:03", "2026-10-25 11:06:33", 5, 1, NULL, "Caja pricipal", "e076120c-e89c-2fec-1383-9047d6245623");

/*!40000 ALTER TABLE `cashiers` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table composite_components
# ------------------------------------------------------------

DROP TABLE IF EXISTS `composite_components`;

CREATE TABLE `composite_components` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quantity` int NOT NULL,
  `unit` varchar(30) NOT NULL,
  `position` int NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `composite_id` int NOT NULL,
  `product_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `costPrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `IDX_7986700251952D53` (`composite_id`),
  KEY `IDX_798670024584665A` (`product_id`),
  KEY `IDX_798670023174800F` (`createdBy_id`),
  KEY `IDX_7986700265FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_798670023174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_798670024584665A` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_7986700251952D53` FOREIGN KEY (`composite_id`) REFERENCES `composites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7986700265FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `composite_components` WRITE;
/*!40000 ALTER TABLE `composite_components` DISABLE KEYS */;

INSERT INTO `composite_components` (`id`, `quantity`, `unit`, `position`, `createdAt`, `updatedAt`, `composite_id`, `product_id`, `createdBy_id`, `updatedBy_id`, `costPrice`, `salePrice`) VALUES
	(1, 300, "gramo", 0, "2026-10-25 05:49:10", "2026-10-25 06:01:30", 1, 1, NULL, NULL, 1800, 3200),
	(2, 100, "gramo", 1, "2026-10-25 05:49:10", "2026-10-25 06:01:30", 1, 2, NULL, NULL, 1000, 1800),
	(3, 1, "unidad", 2, "2026-10-25 05:49:10", "2026-10-25 06:01:30", 1, 3, NULL, NULL, 7000, 10500),
	(4, 250, "ml", 3, "2026-10-25 05:49:10", "2026-10-25 06:01:30", 1, 4, NULL, NULL, 1500, 2500);

/*!40000 ALTER TABLE `composite_components` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table composites
# ------------------------------------------------------------

DROP TABLE IF EXISTS `composites`;

CREATE TABLE `composites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `code` varchar(120) DEFAULT NULL,
  `description` longtext,
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  `active` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `costPrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7E861CC077153098` (`code`),
  KEY `IDX_7E861CC03174800F` (`createdBy_id`),
  KEY `IDX_7E861CC065FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_7E861CC03174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7E861CC065FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `composites` WRITE;
/*!40000 ALTER TABLE `composites` DISABLE KEYS */;

INSERT INTO `composites` (`id`, `name`, `code`, `description`, `salePrice`, `active`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`, `costPrice`) VALUES
	(1, "Arroz, papa, costilla y gaseosa", "demo-almuerzo-18000", "Compuesto de prueba para validar el menú de venta.", 18000, 1, "2026-10-25 05:49:10", "2026-10-25 06:01:30", NULL, NULL, 11300);

/*!40000 ALTER TABLE `composites` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table doctrine_migration_versions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `doctrine_migration_versions`;

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
	("DoctrineMigrations\\Version20261025052947", "2026-10-25 05:29:53", 480),
	("DoctrineMigrations\\Version20261025053140", "2026-10-25 05:31:46", 663),
	("DoctrineMigrations\\Version20261025053239", "2026-10-25 05:32:45", 667),
	("DoctrineMigrations\\Version20261025053347", "2026-10-25 05:33:56", 908),
	("DoctrineMigrations\\Version20261025054008", "2026-10-25 05:43:04", 111),
	("DoctrineMigrations\\Version20261025060119", "2026-10-25 06:01:30", 81),
	("DoctrineMigrations\\Version20261025060210", "2026-10-25 06:02:35", 2),
	("DoctrineMigrations\\Version20261025060220", "2026-10-25 06:02:47", 20),
	("DoctrineMigrations\\Version20261025060300", "2026-10-25 06:08:01", 18),
	("DoctrineMigrations\\Version20261025060400", "2026-10-25 06:28:20", 209),
	("DoctrineMigrations\\Version20261025063149", "2026-10-25 06:32:23", 151),
	("DoctrineMigrations\\Version20261025071028", "2026-10-25 07:10:33", 70),
	("DoctrineMigrations\\Version20261025071416", "2026-10-25 07:14:20", 144),
	("DoctrineMigrations\\Version20261025075957", "2026-10-25 08:00:05", 21),
	("DoctrineMigrations\\Version20261025080157", "2026-10-25 08:02:02", 248),
	("DoctrineMigrations\\Version20261025080324", "2026-10-25 08:03:29", 300),
	("DoctrineMigrations\\Version20261025080852", "2026-10-25 08:08:58", 146),
	("DoctrineMigrations\\Version20261025081555", "2026-10-25 08:16:00", 172),
	("DoctrineMigrations\\Version20261025081741", "2026-10-25 08:17:47", 77),
	("DoctrineMigrations\\Version20261025082031", "2026-10-25 08:20:34", 16),
	("DoctrineMigrations\\Version20261025082126", "2026-10-25 08:21:30", 80),
	("DoctrineMigrations\\Version20261025083000", "2026-10-25 08:24:36", 89),
	("DoctrineMigrations\\Version20261025083100", "2026-10-25 08:25:25", 13),
	("DoctrineMigrations\\Version20261025083200", "2026-10-25 08:26:35", 117),
	("DoctrineMigrations\\Version20261025083300", "2026-10-25 08:26:44", 14),
	("DoctrineMigrations\\Version20261025084000", "2026-10-25 08:38:50", 329),
	("DoctrineMigrations\\Version20261025084100", "2026-10-25 08:39:14", 175),
	("DoctrineMigrations\\Version20261025085000", "2026-10-25 08:43:01", 16),
	("DoctrineMigrations\\Version20261025085100", "2026-10-25 08:43:14", 1),
	("DoctrineMigrations\\Version20261025085200", "2026-10-25 08:49:49", 21),
	("DoctrineMigrations\\Version20261025085300", "2026-10-25 08:49:49", 3),
	("DoctrineMigrations\\Version20261025085400", "2026-10-25 08:50:05", 13),
	("DoctrineMigrations\\Version20261025085500", "2026-10-25 09:25:25", 22),
	("DoctrineMigrations\\Version20261025085600", "2026-10-25 10:27:14", 1),
	("DoctrineMigrations\\Version20261025085700", "2026-10-25 10:37:36", 1),
	("DoctrineMigrations\\Version20261025085800", "2026-10-25 10:39:52", 396),
	("DoctrineMigrations\\Version20261025085900", "2026-10-25 10:45:19", 420),
	("DoctrineMigrations\\Version20261025086000", "2026-10-25 10:45:38", 95),
	("DoctrineMigrations\\Version20261025086100", "2026-10-25 10:47:42", 4),
	("DoctrineMigrations\\Version20261025086200", "2026-10-25 10:49:13", 2),
	("DoctrineMigrations\\Version20261025086300", "2026-10-25 10:51:34", 15),
	("DoctrineMigrations\\Version20261025086400", "2026-10-25 10:54:28", 16),
	("DoctrineMigrations\\Version20261025086500", "2026-10-25 10:55:14", 11),
	("DoctrineMigrations\\Version20261025086600", "2026-10-25 10:55:14", 7),
	("DoctrineMigrations\\Version20261025086700", "2026-10-25 11:04:43", 328),
	("DoctrineMigrations\\Version20261025086800", "2026-10-25 11:05:00", 118),
	("DoctrineMigrations\\Version20261025086900", "2026-10-25 11:28:02", 62),
	("DoctrineMigrations\\Version20261025087000", "2026-10-25 11:28:13", 57);

/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table invoices
# ------------------------------------------------------------

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoiceNumber` int NOT NULL,
  `issuedAt` datetime NOT NULL,
  `paymentMethod` varchar(20) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `deliveryFee` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `cashAmount` decimal(12,2) NOT NULL,
  `digitalAmount` decimal(12,2) NOT NULL,
  `cashReceived` decimal(12,2) DEFAULT NULL,
  `changeDue` decimal(12,2) DEFAULT NULL,
  `itemsSnapshot` json NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `order_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6A2F2F958D9F6D38` (`order_id`),
  UNIQUE KEY `uniq_invoices_number` (`invoiceNumber`),
  KEY `IDX_6A2F2F953174800F` (`createdBy_id`),
  KEY `IDX_6A2F2F9565FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_6A2F2F953174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6A2F2F9565FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6A2F2F958D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;

INSERT INTO `invoices` (`id`, `invoiceNumber`, `issuedAt`, `paymentMethod`, `subtotal`, `deliveryFee`, `total`, `cashAmount`, `digitalAmount`, `cashReceived`, `changeDue`, `itemsSnapshot`, `createdAt`, `updatedAt`, `order_id`, `createdBy_id`, `updatedBy_id`) VALUES
	(1, 0, "2026-10-25 10:14:30", "mixto", 18000, 0, 18000, 8000, 10000, 50000, 42000, "[{\"name\":\"Combo 1\",\"total\":\"18000.00\",\"quantity\":1,\"salePrice\":\"18000.00\",\"components\":[{\"name\":\"Arroz\",\"unit\":\"gr\",\"quantity\":300,\"salePrice\":\"3200.00\"},{\"name\":\"Papa\",\"unit\":\"gr\",\"quantity\":100,\"salePrice\":\"1800.00\"},{\"name\":\"Costilla\",\"unit\":\"unid\",\"quantity\":1,\"salePrice\":\"10500.00\"},{\"name\":\"Gaseosa\",\"unit\":\"ml\",\"quantity\":250,\"salePrice\":\"2500.00\"}]}]", "2026-10-25 10:14:30", "2026-10-25 10:14:30", 7, 5, 5),
	(2, 1, "2026-10-25 10:29:09", "efectivo", 42200, 0, 42200, 42200, 0, 50000, 7800, "[{\"name\":\"Combo 1\",\"total\":\"18000.00\",\"quantity\":1,\"salePrice\":\"18000.00\",\"components\":[{\"name\":\"Arroz\",\"unit\":\"gr\",\"quantity\":300,\"salePrice\":\"3200.00\"},{\"name\":\"Papa\",\"unit\":\"gr\",\"quantity\":100,\"salePrice\":\"1800.00\"},{\"name\":\"Costilla\",\"unit\":\"unid\",\"quantity\":1,\"salePrice\":\"10500.00\"},{\"name\":\"Gaseosa\",\"unit\":\"ml\",\"quantity\":250,\"salePrice\":\"2500.00\"}]},{\"name\":\"Combo 2\",\"total\":\"24200.00\",\"quantity\":1,\"salePrice\":\"24200.00\",\"components\":[{\"name\":\"Costilla\",\"unit\":\"unid\",\"quantity\":1,\"salePrice\":\"10500.00\"},{\"name\":\"Costilla\",\"unit\":\"unid\",\"quantity\":1,\"salePrice\":\"10500.00\"},{\"name\":\"Arroz\",\"unit\":\"gr\",\"quantity\":300,\"salePrice\":\"3200.00\"}]}]", "2026-10-25 10:29:09", "2026-10-25 10:29:09", 8, 5, 5);

/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table order_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `order_history`;

CREATE TABLE `order_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(60) NOT NULL,
  `previousStatus` varchar(30) DEFAULT NULL,
  `newStatus` varchar(30) DEFAULT NULL,
  `details` json NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `order_id` int NOT NULL,
  `performedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D1C0D9008D9F6D38` (`order_id`),
  KEY `IDX_D1C0D900AA03708` (`performedBy_id`),
  CONSTRAINT `FK_D1C0D9008D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D1C0D900AA03708` FOREIGN KEY (`performedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `order_history` WRITE;
/*!40000 ALTER TABLE `order_history` DISABLE KEYS */;

INSERT INTO `order_history` (`id`, `action`, `previousStatus`, `newStatus`, `details`, `createdAt`, `updatedAt`, `order_id`, `performedBy_id`) VALUES
	(57, "order.created", NULL, NULL, "{\"items\":1,\"orderNumber\":1}", "2026-10-25 09:35:30", "2026-10-25 09:35:30", 7, 3),
	(58, "kitchen.status_changed", "en_preparacion", "preparada", "[]", "2026-10-25 09:36:14", "2026-10-25 09:36:14", 7, 4),
	(59, "kitchen.status_changed", "preparada", "servido", "[]", "2026-10-25 09:36:17", "2026-10-25 09:36:17", 7, 4),
	(60, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:37:14", "2026-10-25 09:37:14", 7, 5),
	(61, "order.payment_receipt.uploaded", NULL, NULL, "{\"size\":4144782,\"mimeType\":\"image/jpeg\",\"replaced\":false,\"originalName\":\"17929212226423657992813043367948.jpg\"}", "2026-10-25 09:40:28", "2026-10-25 09:40:28", 7, 5),
	(62, "order.payment_receipt.uploaded", NULL, NULL, "{\"size\":2234697,\"mimeType\":\"image/png\",\"replaced\":true,\"originalName\":\"Don Wok_ Emblema Halloween Oriental.png\"}", "2026-10-25 09:43:12", "2026-10-25 09:43:12", 7, 5),
	(63, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:46:30", "2026-10-25 09:46:30", 7, 5),
	(64, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:47:10", "2026-10-25 09:47:10", 7, 5),
	(65, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:47:10", "2026-10-25 09:47:10", 7, 5),
	(66, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:47:15", "2026-10-25 09:47:15", 7, 5),
	(67, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:47:15", "2026-10-25 09:47:15", 7, 5),
	(68, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:47:21", "2026-10-25 09:47:21", 7, 5),
	(69, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:47:21", "2026-10-25 09:47:21", 7, 5),
	(70, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:48:21", "2026-10-25 09:48:21", 7, 5),
	(71, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:48:21", "2026-10-25 09:48:21", 7, 5),
	(72, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:48:49", "2026-10-25 09:48:49", 7, 5),
	(73, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:48:49", "2026-10-25 09:48:49", 7, 5),
	(74, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:49:36", "2026-10-25 09:49:36", 7, 5),
	(75, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:52:12", "2026-10-25 09:52:12", 7, 5),
	(76, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:52:19", "2026-10-25 09:52:19", 7, 5),
	(77, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:53:19", "2026-10-25 09:53:19", 7, 5),
	(78, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:54:13", "2026-10-25 09:54:13", 7, 5),
	(79, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:54:24", "2026-10-25 09:54:24", 7, 5),
	(80, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:55:07", "2026-10-25 09:55:07", 7, 5),
	(81, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:55:08", "2026-10-25 09:55:08", 7, 5),
	(82, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:55:39", "2026-10-25 09:55:39", 7, 5),
	(83, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:56:49", "2026-10-25 09:56:49", 7, 5),
	(84, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:56:50", "2026-10-25 09:56:50", 7, 5),
	(85, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:58:44", "2026-10-25 09:58:44", 7, 5),
	(86, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:58:47", "2026-10-25 09:58:47", 7, 5),
	(87, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:59:34", "2026-10-25 09:59:34", 7, 5),
	(88, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:59:35", "2026-10-25 09:59:35", 7, 5),
	(89, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 09:59:43", "2026-10-25 09:59:43", 7, 5),
	(90, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 09:59:43", "2026-10-25 09:59:43", 7, 5),
	(91, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 10:00:46", "2026-10-25 10:00:46", 7, 5),
	(92, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 10:00:48", "2026-10-25 10:00:48", 7, 5),
	(93, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 10:04:43", "2026-10-25 10:04:43", 7, 5),
	(94, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 10:04:44", "2026-10-25 10:04:44", 7, 5),
	(95, "order.payment_receipt.uploaded", NULL, NULL, "{\"size\":4163484,\"mimeType\":\"image/jpeg\",\"replaced\":true,\"originalName\":\"1792922700876601447079823942371.jpg\"}", "2026-10-25 10:05:07", "2026-10-25 10:05:07", 7, 5),
	(96, "order.payment_receipt.uploaded", NULL, NULL, "{\"size\":4232989,\"mimeType\":\"image/jpeg\",\"replaced\":true,\"originalName\":\"17929227546998115718824536620881.jpg\"}", "2026-10-25 10:06:02", "2026-10-25 10:06:02", 7, 5),
	(97, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"17999.99\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"0.01\"}", "2026-10-25 10:12:14", "2026-10-25 10:12:14", 7, 5),
	(98, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"17999.98\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"0.02\"}", "2026-10-25 10:12:17", "2026-10-25 10:12:17", 7, 5),
	(99, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 10:12:23", "2026-10-25 10:12:23", 7, 5),
	(100, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 10:12:23", "2026-10-25 10:12:23", 7, 5),
	(101, "order.payment_receipt.request_cancelled", NULL, NULL, "[]", "2026-10-25 10:12:26", "2026-10-25 10:12:26", 7, 5),
	(102, "order.payment_receipt.requested", NULL, NULL, "[]", "2026-10-25 10:12:27", "2026-10-25 10:12:27", 7, 5),
	(103, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"17999.00\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"1.00\"}", "2026-10-25 10:13:08", "2026-10-25 10:13:08", 7, 5),
	(104, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"17999.00\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"1.00\"}", "2026-10-25 10:13:11", "2026-10-25 10:13:11", 7, 5),
	(105, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"17999.00\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"1.00\"}", "2026-10-25 10:13:15", "2026-10-25 10:13:15", 7, 5),
	(106, "order.payment_draft_updated", NULL, NULL, "{\"cashAmount\":\"8000.00\",\"deliveryFee\":\"0.00\",\"digitalAmount\":\"10000.00\"}", "2026-10-25 10:13:59", "2026-10-25 10:13:59", 7, 5),
	(107, "order.payment_validated", NULL, NULL, "{\"total\":\"18000.00\",\"changeDue\":\"42000.00\",\"cashAmount\":\"8000.00\",\"allocations\":[{\"amount\":\"8000.00\",\"method\":\"efectivo\",\"cashBoxUuid\":\"1dfbf1fa-d050-11f1-980a-e2c104d05810\"},{\"amount\":\"10000.00\",\"method\":\"digital\",\"cashBoxUuid\":\"4fa7d40f-622e-32c5-2d02-f7ceed5341af\"}],\"deliveryFee\":\"0.00\",\"cashReceived\":\"50000.00\",\"digitalAmount\":\"10000.00\",\"paymentMethod\":\"mixto\"}", "2026-10-25 10:14:30", "2026-10-25 10:14:30", 7, 5),
	(108, "order.invoiced", NULL, NULL, "{\"total\":\"18000.00\",\"invoiceNumber\":0}", "2026-10-25 10:14:30", "2026-10-25 10:14:30", 7, 5),
	(109, "order.created", NULL, NULL, "{\"items\":2,\"orderNumber\":2}", "2026-10-25 10:19:37", "2026-10-25 10:19:37", 8, 3),
	(110, "order.delivered", "servido", "entregada", "[]", "2026-10-25 10:28:49", "2026-10-25 10:28:49", 7, 5),
	(111, "order.payment_validated", NULL, NULL, "{\"total\":\"42200.00\",\"changeDue\":\"7800.00\",\"cashAmount\":\"42200.00\",\"allocations\":[{\"amount\":\"42200.00\",\"method\":\"efectivo\",\"cashBoxUuid\":\"1dfbf1fa-d050-11f1-980a-e2c104d05810\"}],\"deliveryFee\":\"0.00\",\"cashReceived\":\"50000.00\",\"digitalAmount\":\"0.00\",\"paymentMethod\":\"efectivo\"}", "2026-10-25 10:29:09", "2026-10-25 10:29:09", 8, 5),
	(112, "order.invoiced", NULL, NULL, "{\"total\":\"42200.00\",\"invoiceNumber\":1}", "2026-10-25 10:29:09", "2026-10-25 10:29:09", 8, 5),
	(113, "kitchen.status_changed", "en_preparacion", "preparada", "[]", "2026-10-25 10:29:40", "2026-10-25 10:29:40", 8, 4),
	(114, "kitchen.status_changed", "preparada", "empacada", "[]", "2026-10-25 10:29:41", "2026-10-25 10:29:41", 8, 4),
	(115, "order.delivered", "empacada", "entregada", "[]", "2026-10-25 10:29:45", "2026-10-25 10:29:45", 8, 5);

/*!40000 ALTER TABLE `order_history` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table order_item_components
# ------------------------------------------------------------

DROP TABLE IF EXISTS `order_item_components`;

CREATE TABLE `order_item_components` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sourceProductId` int DEFAULT NULL,
  `productName` varchar(180) NOT NULL,
  `quantity` int NOT NULL,
  `unit` varchar(30) NOT NULL,
  `costPrice` decimal(12,2) NOT NULL,
  `position` int NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `orderItem_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `IDX_98F01EABE76E9C94` (`orderItem_id`),
  KEY `IDX_98F01EAB3174800F` (`createdBy_id`),
  KEY `IDX_98F01EAB65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_98F01EAB3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_98F01EAB65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_98F01EABE76E9C94` FOREIGN KEY (`orderItem_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `order_item_components` WRITE;
/*!40000 ALTER TABLE `order_item_components` DISABLE KEYS */;

INSERT INTO `order_item_components` (`id`, `sourceProductId`, `productName`, `quantity`, `unit`, `costPrice`, `position`, `createdAt`, `updatedAt`, `orderItem_id`, `createdBy_id`, `updatedBy_id`, `salePrice`) VALUES
	(84, 1, "Arroz", 300, "gr", 1800, 0, "2026-10-25 09:35:30", "2026-10-25 09:35:30", 22, NULL, NULL, 3200),
	(85, 2, "Papa", 100, "gr", 1000, 1, "2026-10-25 09:35:30", "2026-10-25 09:35:30", 22, NULL, NULL, 1800),
	(86, 3, "Costilla", 1, "unid", 7000, 2, "2026-10-25 09:35:30", "2026-10-25 09:35:30", 22, NULL, NULL, 10500),
	(87, 4, "Gaseosa", 250, "ml", 1500, 3, "2026-10-25 09:35:30", "2026-10-25 09:35:30", 22, NULL, NULL, 2500),
	(88, 1, "Arroz", 300, "gr", 1800, 0, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 23, NULL, NULL, 3200),
	(89, 2, "Papa", 100, "gr", 1000, 1, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 23, NULL, NULL, 1800),
	(90, 3, "Costilla", 1, "unid", 7000, 2, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 23, NULL, NULL, 10500),
	(91, 4, "Gaseosa", 250, "ml", 1500, 3, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 23, NULL, NULL, 2500),
	(92, 3, "Costilla", 1, "unid", 7000, 0, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 24, NULL, NULL, 10500),
	(93, 3, "Costilla", 1, "unid", 7000, 1, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 24, NULL, NULL, 10500),
	(94, 1, "Arroz", 300, "gr", 1800, 2, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 24, NULL, NULL, 3200);

/*!40000 ALTER TABLE `order_item_components` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table order_items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sourceType` varchar(20) NOT NULL,
  `sourceId` int DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `quantity` int NOT NULL,
  `costPrice` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `position` int NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `order_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `IDX_62809DB08D9F6D38` (`order_id`),
  KEY `IDX_62809DB03174800F` (`createdBy_id`),
  KEY `IDX_62809DB065FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_62809DB03174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_62809DB065FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_62809DB08D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;

INSERT INTO `order_items` (`id`, `sourceType`, `sourceId`, `name`, `quantity`, `costPrice`, `total`, `position`, `createdAt`, `updatedAt`, `order_id`, `createdBy_id`, `updatedBy_id`, `salePrice`) VALUES
	(22, "custom_composite", NULL, "Combo 1", 1, 11300, 18000, 0, "2026-10-25 09:35:30", "2026-10-25 09:35:30", 7, NULL, NULL, 18000),
	(23, "custom_composite", NULL, "Combo 1", 1, 11300, 18000, 0, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 8, NULL, NULL, 18000),
	(24, "custom_composite", NULL, "Combo 2", 1, 15800, 24200, 1, "2026-10-25 10:19:37", "2026-10-25 10:19:37", 8, NULL, NULL, 24200);

/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table order_payment_allocations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `order_payment_allocations`;

CREATE TABLE `order_payment_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `cashBox_id` int NOT NULL,
  `method` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F923AA568D9F6D38` (`order_id`),
  KEY `IDX_F923AA563174800F` (`createdBy_id`),
  KEY `IDX_F923AA5665FF1AEC` (`updatedBy_id`),
  KEY `IDX_F923AA5666BD09B9` (`cashBox_id`),
  CONSTRAINT `FK_5A0E9BB28D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5A0E9BB2B03A8386` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_5A0E9BB2D9A8B7E0` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_F923AA5666BD09B9` FOREIGN KEY (`cashBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `order_payment_allocations` WRITE;
/*!40000 ALTER TABLE `order_payment_allocations` DISABLE KEYS */;

INSERT INTO `order_payment_allocations` (`id`, `order_id`, `cashBox_id`, `method`, `amount`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`) VALUES
	(13, 7, 6, "efectivo", 8000, "2026-10-25 10:14:30", "2026-10-25 10:14:30", 5, 5),
	(14, 7, 2, "digital", 10000, "2026-10-25 10:14:30", "2026-10-25 10:14:30", 5, 5),
	(15, 8, 6, "efectivo", 42200, "2026-10-25 10:29:09", "2026-10-25 10:29:09", 5, 5);

/*!40000 ALTER TABLE `order_payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table orders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orderDate` date NOT NULL,
  `orderNumber` int NOT NULL,
  `orderType` varchar(20) NOT NULL,
  `operationalStatus` varchar(30) NOT NULL,
  `billingStatus` varchar(20) NOT NULL,
  `tableNumber` varchar(60) DEFAULT NULL,
  `customerName` varchar(180) DEFAULT NULL,
  `customerPhone` varchar(40) DEFAULT NULL,
  `deliveryAddress` varchar(255) DEFAULT NULL,
  `lockedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `assignedCashier_id` int DEFAULT NULL,
  `lockedBy_id` int DEFAULT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `paymentReceiptKey` varchar(255) DEFAULT NULL,
  `paymentReceiptOriginalName` varchar(180) DEFAULT NULL,
  `paymentReceiptMimeType` varchar(100) DEFAULT NULL,
  `paymentReceiptUploadedAt` datetime DEFAULT NULL,
  `paymentReceiptUploadedBy_id` int DEFAULT NULL,
  `paymentMethod` varchar(20) DEFAULT NULL,
  `deliveryFee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cashAmount` decimal(12,2) DEFAULT NULL,
  `digitalAmount` decimal(12,2) DEFAULT NULL,
  `cashReceived` decimal(12,2) DEFAULT NULL,
  `changeDue` decimal(12,2) DEFAULT NULL,
  `paymentReceiptRequested` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_orders_date_number` (`orderDate`,`orderNumber`),
  KEY `IDX_E52FFDEE55BB13E6` (`assignedCashier_id`),
  KEY `IDX_E52FFDEE1E253D71` (`lockedBy_id`),
  KEY `IDX_E52FFDEE3174800F` (`createdBy_id`),
  KEY `IDX_E52FFDEE65FF1AEC` (`updatedBy_id`),
  KEY `IDX_E52FFDEE827FB8B1` (`paymentReceiptUploadedBy_id`),
  CONSTRAINT `FK_E52FFDEE1E253D71` FOREIGN KEY (`lockedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E52FFDEE3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E52FFDEE55BB13E6` FOREIGN KEY (`assignedCashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E52FFDEE65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E52FFDEE827FB8B1` FOREIGN KEY (`paymentReceiptUploadedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;

INSERT INTO `orders` (`id`, `orderDate`, `orderNumber`, `orderType`, `operationalStatus`, `billingStatus`, `tableNumber`, `customerName`, `customerPhone`, `deliveryAddress`, `lockedAt`, `createdAt`, `updatedAt`, `assignedCashier_id`, `lockedBy_id`, `createdBy_id`, `updatedBy_id`, `paymentReceiptKey`, `paymentReceiptOriginalName`, `paymentReceiptMimeType`, `paymentReceiptUploadedAt`, `paymentReceiptUploadedBy_id`, `paymentMethod`, `deliveryFee`, `cashAmount`, `digitalAmount`, `cashReceived`, `changeDue`, `paymentReceiptRequested`) VALUES
	(7, "2026-10-25", 1, "mesa", "entregada", "facturada", "2", NULL, NULL, NULL, NULL, "2026-10-25 09:35:30", "2026-10-25 10:28:49", 5, NULL, 3, 5, "orders/2026-10-25/1/receipt-02ed72f821708dd7.jpg", "17929227546998115718824536620881.jpg", "image/jpeg", "2026-10-25 10:06:02", 5, "mixto", 0, 8000, 10000, 50000, 42000, 1),
	(8, "2026-10-25", 2, "llevar", "entregada", "facturada", NULL, NULL, NULL, NULL, NULL, "2026-10-25 10:19:37", "2026-10-25 10:29:45", 5, NULL, 3, 5, NULL, NULL, NULL, NULL, NULL, "efectivo", 0, 42200, 0, 50000, 7800, 0);

/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table permission_group_permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `permission_group_permissions`;

CREATE TABLE `permission_group_permissions` (
  `permissiongroup_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`permissiongroup_id`,`permission_id`),
  KEY `IDX_A305715EBD0DD96D` (`permissiongroup_id`),
  KEY `IDX_A305715EFED90CCA` (`permission_id`),
  CONSTRAINT `FK_A305715EBD0DD96D` FOREIGN KEY (`permissiongroup_id`) REFERENCES `permission_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A305715EFED90CCA` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `permission_group_permissions` WRITE;
/*!40000 ALTER TABLE `permission_group_permissions` DISABLE KEYS */;

INSERT INTO `permission_group_permissions` (`permissiongroup_id`, `permission_id`) VALUES
	(1, 1),
	(1, 2),
	(1, 3),
	(1, 4),
	(1, 5),
	(1, 6),
	(1, 7),
	(1, 8),
	(1, 9),
	(1, 10),
	(1, 11),
	(1, 12),
	(1, 13),
	(1, 14),
	(1, 15),
	(1, 16),
	(1, 17),
	(1, 18),
	(1, 19),
	(1, 20),
	(1, 21),
	(1, 22),
	(1, 23),
	(1, 24),
	(1, 25),
	(1, 26),
	(1, 27),
	(1, 28),
	(1, 29),
	(1, 30),
	(1, 31),
	(1, 32),
	(1, 33),
	(1, 34),
	(1, 35),
	(1, 36),
	(1, 37),
	(1, 38),
	(1, 39),
	(1, 40),
	(1, 41),
	(1, 42),
	(1, 43),
	(1, 44),
	(1, 45),
	(1, 46),
	(1, 47),
	(1, 49),
	(2, 1),
	(2, 2),
	(2, 3),
	(2, 4),
	(2, 5),
	(2, 6),
	(2, 7),
	(2, 9),
	(2, 10),
	(2, 11),
	(2, 12),
	(2, 13),
	(2, 14),
	(2, 15),
	(2, 16),
	(3, 1),
	(3, 25),
	(3, 26),
	(3, 27),
	(4, 1),
	(4, 4),
	(4, 9),
	(4, 10),
	(4, 28),
	(4, 29),
	(4, 31),
	(4, 32),
	(4, 33),
	(4, 34),
	(4, 35),
	(4, 37),
	(4, 48);

/*!40000 ALTER TABLE `permission_group_permissions` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table permission_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `permission_groups`;

CREATE TABLE `permission_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_39B79E9B5E237E06` (`name`),
  KEY `IDX_39B79E9B3174800F` (`createdBy_id`),
  KEY `IDX_39B79E9B65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_39B79E9B3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_39B79E9B65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `permission_groups` WRITE;
/*!40000 ALTER TABLE `permission_groups` DISABLE KEYS */;

INSERT INTO `permission_groups` (`id`, `name`, `description`, `active`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`) VALUES
	(1, "supervisor", "Administración operativa mediante permisos configurables.", 1, "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(2, "mesero", "Creación y gestión de órdenes según el flujo del mesero.", 1, "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(3, "cocinero", "Consulta y actualización de comandas en cocina.", 1, "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(4, "cajero", "Facturación y operación de cajas asignadas.", 1, "2026-10-25 06:11:04", "2026-10-25 07:21:06", NULL, NULL);

/*!40000 ALTER TABLE `permission_groups` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(120) NOT NULL,
  `name` varchar(180) NOT NULL,
  `resource` varchar(180) NOT NULL,
  `action` varchar(30) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2DEDCC6F77153098` (`code`),
  KEY `IDX_2DEDCC6F3174800F` (`createdBy_id`),
  KEY `IDX_2DEDCC6F65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_2DEDCC6F3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2DEDCC6F65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;

INSERT INTO `permissions` (`id`, `code`, `name`, `resource`, `action`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`) VALUES
	(1, "dashboard.read", "Ver panel operativo", "dashboard", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(2, "sale.read", "Ver menú de venta", "sale", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(3, "sale.create", "Crear órdenes desde venta", "sale", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(4, "orders.read", "Consultar órdenes", "orders", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(5, "orders.create", "Crear órdenes", "orders", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(6, "orders.update", "Editar órdenes no facturadas", "orders", "update", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(7, "orders.select_cashier", "Seleccionar cajero", "orders", "select_cashier", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(8, "orders.change_table", "Cambiar mesa", "orders", "change_table", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(9, "orders.deliver_table", "Marcar entrega en mesa", "orders", "deliver_table", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(10, "orders.upload_receipt", "Cargar comprobantes", "orders", "upload_receipt", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(11, "orders.print", "Imprimir comandas", "orders", "print", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(12, "orders.reprint", "Reimprimir comandas", "orders", "reprint", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(13, "order_components.read", "Ver componentes de órdenes", "order_components", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(14, "order_components.create", "Agregar componentes", "order_components", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(15, "order_components.replace", "Reemplazar componentes", "order_components", "replace", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(16, "order_components.delete", "Quitar componentes", "order_components", "delete", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(17, "catalog_products.read", "Consultar productos", "catalog_products", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(18, "catalog_products.create", "Crear productos", "catalog_products", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(19, "catalog_products.update", "Actualizar productos", "catalog_products", "update", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(20, "catalog_products.delete", "Desactivar productos", "catalog_products", "delete", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(21, "catalog_composites.read", "Consultar compuestos", "catalog_composites", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(22, "catalog_composites.create", "Crear compuestos", "catalog_composites", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(23, "catalog_composites.update", "Actualizar compuestos", "catalog_composites", "update", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(24, "catalog_composites.delete", "Desactivar compuestos", "catalog_composites", "delete", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(25, "kitchen.read", "Ver comandas de cocina", "kitchen", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(26, "kitchen.update_status", "Cambiar estado en cocina", "kitchen", "update_status", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(27, "kitchen.reject", "Rechazar comanda con motivo", "kitchen", "reject", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(28, "billing.read", "Consultar facturación", "billing", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(29, "billing.create", "Facturar órdenes", "billing", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(30, "billing.cancel", "Anular facturas", "billing", "cancel", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(31, "cash.read", "Consultar cajas y movimientos", "cash", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(32, "cash.activate", "Activar caja", "cash", "activate", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(33, "cash.deactivate", "Desactivar caja", "cash", "deactivate", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(34, "cash.egress", "Registrar egresos", "cash", "egress", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(35, "cash.transfer", "Trasladar dinero entre cajas", "cash", "transfer", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(36, "cash.audit", "Arqueo de caja", "cash", "audit", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(37, "cash.close", "Cerrar caja", "cash", "close", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(38, "cash.reprint_close", "Reimprimir cierre de caja", "cash", "reprint_close", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(39, "groups.read", "Consultar grupos y permisos", "groups", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(40, "groups.create", "Crear grupos", "groups", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(41, "groups.update", "Actualizar grupos y permisos", "groups", "update", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(42, "groups.delete", "Desactivar grupos", "groups", "delete", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(43, "users.read", "Consultar usuarios", "users", "read", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(44, "users.create", "Crear usuarios", "users", "create", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(45, "users.update", "Actualizar usuarios", "users", "update", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(46, "users.delete", "Desactivar usuarios", "users", "delete", "2026-10-25 06:11:04", "2026-10-25 06:11:04", NULL, NULL),
	(47, "cash.ledger.read", "Consultar libro mayor", "cash", "ledger_read", "2026-10-25 10:47:42", "2026-10-25 10:47:42", NULL, NULL),
	(48, "cash.ledger.read_own", "Consultar movimientos propios", "cash", "ledger_read_own", "2026-10-25 10:49:13", "2026-10-25 10:49:13", NULL, NULL),
	(49, "cash.ledger.approve", "Aprobar egresos", "cash", "ledger_approve", "2026-10-25 10:55:14", "2026-10-25 10:55:14", NULL, NULL);

/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table products
# ------------------------------------------------------------

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `code` varchar(120) DEFAULT NULL,
  `description` longtext,
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(30) NOT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `costPrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  `measure` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B3BA5A5A77153098` (`code`),
  KEY `IDX_B3BA5A5A3174800F` (`createdBy_id`),
  KEY `IDX_B3BA5A5A65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_B3BA5A5A3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_B3BA5A5A65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;

INSERT INTO `products` (`id`, `name`, `code`, `description`, `salePrice`, `unit`, `active`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`, `costPrice`, `measure`) VALUES
	(1, "Arroz", "demo-arroz-300g", NULL, 3200, "gr", 1, "2026-10-25 05:49:10", "2026-10-25 07:06:34", NULL, NULL, 1800, 300),
	(2, "Papa", "demo-papa-100g", NULL, 1800, "gr", 1, "2026-10-25 05:49:10", "2026-10-25 07:07:09", NULL, NULL, 1000, 100),
	(3, "Costilla", "demo-costilla-1u", NULL, 10500, "unid", 1, "2026-10-25 05:49:10", "2026-10-25 07:06:57", NULL, NULL, 7000, 1),
	(4, "Gaseosa", "demo-gaseosa-250ml", NULL, 2500, "ml", 1, "2026-10-25 05:49:10", "2026-10-25 06:08:01", NULL, NULL, 1500, 250);

/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table temporary_cash_boxes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `temporary_cash_boxes`;

CREATE TABLE `temporary_cash_boxes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `closure_id` int NOT NULL,
  `sourceBox_id` int NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_36C30086D17F50A6` (`uuid`),
  KEY `IDX_36C30086FA43E9DA` (`closure_id`),
  KEY `IDX_36C30086CC8B49AA` (`sourceBox_id`),
  KEY `IDX_36C300863174800F` (`createdBy_id`),
  KEY `IDX_36C3008665FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_TEMP_CASH_CLOSURE` FOREIGN KEY (`closure_id`) REFERENCES `cash_closures` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_TEMP_CASH_CREATED_BY` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_TEMP_CASH_SOURCE` FOREIGN KEY (`sourceBox_id`) REFERENCES `cash_boxes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_TEMP_CASH_UPDATED_BY` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





# Dump of table user_permission_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_permission_groups`;

CREATE TABLE `user_permission_groups` (
  `permissiongroup_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`permissiongroup_id`,`user_id`),
  KEY `IDX_C1A825B0BD0DD96D` (`permissiongroup_id`),
  KEY `IDX_C1A825B0A76ED395` (`user_id`),
  CONSTRAINT `FK_C1A825B0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C1A825B0BD0DD96D` FOREIGN KEY (`permissiongroup_id`) REFERENCES `permission_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `user_permission_groups` WRITE;
/*!40000 ALTER TABLE `user_permission_groups` DISABLE KEYS */;

INSERT INTO `user_permission_groups` (`permissiongroup_id`, `user_id`) VALUES
	(1, 2),
	(2, 3),
	(3, 4),
	(4, 5);

/*!40000 ALTER TABLE `user_permission_groups` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `username` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9F85E0677` (`username`),
  KEY `IDX_1483A5E93174800F` (`createdBy_id`),
  KEY `IDX_1483A5E965FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_1483A5E93174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1483A5E965FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `roles`, `password`, `active`, `createdAt`, `updatedAt`, `createdBy_id`, `updatedBy_id`, `username`) VALUES
	(1, "[\"ROLE_ADMIN\"]", "$2y$13$fgHe2pP/6375.Uit1NnJqeZN.QBot5/OwPSldtDNT9mZ8OLl0i/Zi", 1, "2026-10-25 05:43:04", "2026-10-25 05:43:04", NULL, NULL, "admin"),
	(2, "[\"ROLE_USER\"]", "$2y$13$juCQ2yJhnKWxd1vDF0iw8OUVfaM2jGdIoIpEBbrmS85ySHD/upzcu", 1, "2026-10-25 05:43:04", "2026-10-25 05:43:04", NULL, NULL, "supervisor"),
	(3, "[\"ROLE_USER\"]", "$2y$13$MHNGz3YLQza0nD3NuXT77Oe8oT860q1xGmet1JKvrTLFrwtnopRc6", 1, "2026-10-25 05:43:04", "2026-10-25 05:43:04", NULL, NULL, "mesero"),
	(4, "[\"ROLE_USER\"]", "$2y$13$60lAFROwXicLfQgqv8gTn.v2yYPoM55znbVPkpVbSEh7pDt5DVcr2", 1, "2026-10-25 05:43:05", "2026-10-25 05:43:05", NULL, NULL, "cocinero"),
	(5, "[\"ROLE_USER\"]", "$2y$13$XwffVzIsW9svUWxne5VJnO/tmST8vcEdZlszBWOwjFcSZ7yQJgVoK", 1, "2026-10-25 05:43:05", "2026-10-25 06:25:19", NULL, NULL, "cajero");

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table work_positions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `work_positions`;

CREATE TABLE `work_positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accessKeyHash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contractType` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hourlyRate` decimal(12,2) DEFAULT NULL,
  `minimumDenomination` int NOT NULL DEFAULT '50',
  `activeDays` json NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B4974D2977153098` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





# Dump of table work_shifts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `work_shifts`;

CREATE TABLE `work_shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_id` int NOT NULL,
  `createdBy_id` int DEFAULT NULL,
  `updatedBy_id` int DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `startedAt` datetime NOT NULL,
  `closedAt` datetime DEFAULT NULL,
  `hourlyRate` decimal(12,2) NOT NULL,
  `minimumDenomination` int NOT NULL,
  `amountToPay` decimal(12,2) DEFAULT NULL,
  `closeCodeHash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6E4B70DDD842E46` (`position_id`),
  KEY `IDX_B6E4B70D3174800F` (`createdBy_id`),
  KEY `IDX_B6E4B70D65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_WORK_SHIFT_CREATED_BY` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_WORK_SHIFT_POSITION` FOREIGN KEY (`position_id`) REFERENCES `work_positions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_WORK_SHIFT_UPDATED_BY` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





# Dump of views
# ------------------------------------------------------------

# Creating temporary tables to overcome VIEW dependency errors


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

# Dump completed on 2026-10-25T07:36:31-05:00
