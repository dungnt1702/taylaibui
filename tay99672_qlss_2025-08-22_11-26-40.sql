/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.21-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tay99672_qlss
-- ------------------------------------------------------
-- Server version	10.6.21-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `xe` int(11) DEFAULT NULL,
  `bat_dau` datetime DEFAULT NULL,
  `ket_thuc` datetime DEFAULT NULL,
  `thoi_gian` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'0943036579','TAY LÁI BỤI','@TayLaiBui193#',1,1),(2,'0986184151','Mr Bình','@TayLaiBui#',0,1),(4,'0988765626','Mr Long','@TayLaiBui#',0,1),(5,'0843346838','Dương Quyền','@TayLaiBui#',0,1),(6,'0948296567','Sơn Nguyễn','@TayLaiBui#',0,1),(7,'0961877244','Trang Trần','@TayLaiBui#',0,1),(8,'0395278768','Hoàng Nguyễn','@TayLaiBui#',0,1),(9,'0866162837','Phúc Lưu','@TayLaiBui#',0,1),(10,'0969299102','Huy Hồ','@TayLaiBui#',0,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `endAt` bigint(20) DEFAULT NULL,
  `paused` tinyint(1) DEFAULT 0,
  `remaining` int(11) DEFAULT NULL,
  `minutes` int(11) DEFAULT NULL,
  `notifiedEnd` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `routeNumber` tinyint(3) unsigned DEFAULT 0,
  `routeStartAt` bigint(20) unsigned DEFAULT 0,
  `repairNotes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,1,NULL,0,NULL,NULL,0,'2025-08-19 17:00:55',3,1755619624115,NULL),(2,1,NULL,0,NULL,NULL,0,'2025-08-19 17:00:59',3,1755620423930,NULL),(3,0,NULL,0,NULL,NULL,0,'2025-08-20 09:16:18',0,0,'Kiểm tra tình trạng'),(4,1,1755676458847,1,741,90,1,'2025-08-20 07:41:57',0,0,'CÃ²n nhiá»u váº¥n Ä‘á» cáº§n kiá»ƒm tra thÆ°á»›c lÃ¡i\nCáº§n kiá»ƒm tra phanh'),(5,0,NULL,0,NULL,NULL,0,'2025-08-20 09:16:39',0,0,'Xe có vấn đề\nCần kiểm tra'),(6,1,NULL,0,NULL,NULL,0,'2025-08-19 16:21:16',6,1755620476621,NULL),(7,1,1755671027300,0,NULL,50,1,'2025-08-22 03:48:48',0,0,NULL),(8,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:06',0,0,NULL),(9,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:08',0,0,NULL),(10,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517898,'Xe Ä‘ang há»ng\nNhiá»u chá»— gáº·p trá»¥c tráº·c'),(11,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517897,NULL),(12,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517895,NULL),(13,1,NULL,0,NULL,NULL,0,'2025-08-19 20:48:38',4,1755636517893,NULL),(14,0,NULL,0,NULL,NULL,0,'2025-08-20 09:16:49',0,0,'Bị cái gì đó'),(15,1,NULL,0,NULL,NULL,0,'2025-08-21 06:58:08',0,0,NULL),(16,1,NULL,0,NULL,NULL,0,'2025-08-11 04:58:58',0,0,NULL),(17,1,NULL,0,NULL,NULL,0,'2025-08-17 08:22:19',0,0,NULL),(18,1,NULL,0,NULL,NULL,0,'2025-08-18 05:34:08',0,0,NULL),(19,1,NULL,0,NULL,NULL,0,'2025-08-17 05:34:30',0,0,NULL),(20,1,NULL,0,NULL,NULL,0,'2025-08-17 08:22:16',0,0,NULL),(21,1,NULL,0,NULL,NULL,0,'2025-08-17 07:21:49',0,0,NULL),(22,0,NULL,0,NULL,NULL,0,'2025-08-20 09:17:00',0,0,'Đang kiểm tra');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

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

-- Dump completed on 2025-08-22 11:26:58
