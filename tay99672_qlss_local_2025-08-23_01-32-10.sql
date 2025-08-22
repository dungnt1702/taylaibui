-- MySQL dump 10.13  Distrib 9.4.0, for macos14.7 (x86_64)
--
-- Host: localhost    Database: tay99672_qlss
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `xe` int DEFAULT NULL,
  `bat_dau` datetime DEFAULT NULL,
  `ket_thuc` datetime DEFAULT NULL,
  `thoi_gian` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_history`
--

DROP TABLE IF EXISTS `maintenance_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tình trạng xe',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú chi tiết',
  `user_id` int NOT NULL COMMENT 'ID người cập nhật',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_history`
--

LOCK TABLES `maintenance_history` WRITE;
/*!40000 ALTER TABLE `maintenance_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_history`
--

DROP TABLE IF EXISTS `repair_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `repair_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại sửa chữa',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả chi tiết sửa chữa',
  `cost` decimal(10,2) DEFAULT NULL COMMENT 'Chi phí sửa chữa',
  `repair_date` date NOT NULL COMMENT 'Ngày sửa chữa',
  `completed_date` date DEFAULT NULL COMMENT 'Ngày hoàn thành',
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Trạng thái sửa chữa',
  `technician` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Thợ sửa chữa',
  `user_id` int NOT NULL COMMENT 'ID người tạo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `user_id` (`user_id`),
  KEY `repair_date` (`repair_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_history`
--

LOCK TABLES `repair_history` WRITE;
/*!40000 ALTER TABLE `repair_history` DISABLE KEYS */;
INSERT INTO `repair_history` VALUES (1,1,'Thay dầu','Thay dầu động cơ và lọc dầu',150000.00,'2025-08-22',NULL,'completed','Thợ A',1,'2025-08-22 09:56:10','2025-08-22 09:56:10'),(2,2,'Sửa máy','Sửa chữa động cơ bị hỏng',500000.00,'2025-08-22',NULL,'in_progress','Thợ B',1,'2025-08-22 09:56:10','2025-08-22 09:56:10'),(3,3,'Hỏng hóc','Thay hệ thống phanh mới',300000.00,'2025-08-22',NULL,'completed','Thợ C',1,'2025-08-22 09:56:10','2025-08-22 12:22:34'),(4,4,'Thay Dầu','Xử lý các vấn đề dầu phanh',20000.00,'2025-08-22',NULL,'pending','Quyền',1,'2025-08-22 10:28:54','2025-08-22 10:28:54'),(5,3,'Sửa chữa','Xịt lốp và đã thay xong',0.00,'2025-08-23',NULL,'completed','Trang',1,'2025-08-22 12:15:41','2025-08-22 12:22:53'),(6,5,'Sửa chữa','Xe đang có vấn đề',0.00,'2025-08-24',NULL,'pending','Huy',1,'2025-08-22 12:22:05','2025-08-22 12:22:05'),(7,5,'Thay thế','Cần thay lốp',0.00,'2025-08-29',NULL,'in_progress','Phúc',4,'2025-08-22 15:06:30','2025-08-22 15:06:30');
/*!40000 ALTER TABLE `repair_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_admin` tinyint DEFAULT '0',
  `is_active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'0943036579','TAY LÁI BỤI','@TayLaiBui193#',1,1),(2,'0986184151','Mr Bình','@TayLaiBui#',0,1),(4,'0988765626','Mr Long Thái','@TayLaiBui#',0,1),(5,'0843346838','Dương Quyền','@TayLaiBui#',0,1),(6,'0948296567','Sơn Nguyễn','@TayLaiBui#',0,1),(7,'0961877244','Trang Trần','@TayLaiBui#',0,1),(8,'0395278768','Hoàng Nguyễn','@TayLaiBui#',0,1),(9,'0866162837','Phúc Lưu','@TayLaiBui#',0,1),(10,'0969299102','Huy Hồ','@TayLaiBui#',0,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int NOT NULL,
  `active` tinyint(1) DEFAULT '1',
  `endAt` bigint DEFAULT NULL,
  `paused` tinyint(1) DEFAULT '0',
  `remaining` int DEFAULT NULL,
  `minutes` int DEFAULT NULL,
  `notifiedEnd` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `routeNumber` tinyint unsigned DEFAULT '0',
  `routeStartAt` bigint unsigned DEFAULT '0',
  `repairNotes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_maintenance_id` int DEFAULT NULL,
  `last_repair_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `last_maintenance_id` (`last_maintenance_id`),
  KEY `last_repair_id` (`last_repair_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (0,0,NULL,0,NULL,NULL,0,'2025-08-22 12:32:01',0,0,NULL,NULL,NULL),(1,1,1755875026397,0,NULL,30,1,'2025-08-22 17:51:40',0,0,NULL,NULL,NULL),(2,1,NULL,0,NULL,NULL,0,'2025-08-22 13:07:05',0,0,NULL,NULL,NULL),(3,1,1755877026456,0,NULL,55,1,'2025-08-22 17:51:40',0,0,NULL,NULL,NULL),(4,1,NULL,0,NULL,NULL,0,'2025-08-22 10:28:54',0,0,'CÃ²n nhiá»u váº¥n Ä‘á» cáº§n kiá»ƒm tra thÆ°á»›c lÃ¡i\nCáº§n kiá»ƒm tra phanh',NULL,4),(5,0,NULL,0,NULL,NULL,0,'2025-08-22 15:06:30',0,0,'Xe có vấn đề\nCần kiểm tra',NULL,7),(6,1,NULL,0,NULL,NULL,0,'2025-08-19 16:21:16',6,1755620476621,NULL,NULL,NULL),(7,1,NULL,0,NULL,NULL,0,'2025-08-22 09:51:49',0,0,NULL,NULL,NULL),(8,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:06',0,0,NULL,NULL,NULL),(9,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:08',0,0,NULL,NULL,NULL),(10,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517898,'Xe Ä‘ang há»ng\nNhiá»u chá»— gáº·p trá»¥c tráº·c',NULL,NULL),(11,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517897,NULL,NULL,NULL),(12,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517895,NULL,NULL,NULL),(13,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517893,NULL,NULL,NULL),(14,1,NULL,0,NULL,NULL,0,'2025-08-22 14:46:36',0,0,'Bị cái gì đó',NULL,NULL),(15,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:08',0,0,NULL,NULL,NULL),(16,1,NULL,0,NULL,NULL,0,'2025-08-11 04:58:58',0,0,NULL,NULL,NULL),(17,1,NULL,0,NULL,NULL,0,'2025-08-17 08:22:19',0,0,NULL,NULL,NULL),(18,1,NULL,0,NULL,NULL,0,'2025-08-18 05:34:08',0,0,NULL,NULL,NULL),(19,1,NULL,0,NULL,NULL,0,'2025-08-17 05:34:30',0,0,NULL,NULL,NULL),(20,1,NULL,0,NULL,NULL,0,'2025-08-17 08:22:16',0,0,NULL,NULL,NULL),(21,1,NULL,0,NULL,NULL,0,'2025-08-17 07:21:49',0,0,NULL,NULL,NULL),(22,0,NULL,0,NULL,NULL,0,'2025-08-20 09:17:00',0,0,'Đang kiểm tra',NULL,NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tay99672_qlss'
--

--
-- Dumping routines for database 'tay99672_qlss'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-23  1:32:11
