/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: remotewfh
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `is_wfh` tinyint(1) NOT NULL DEFAULT 0,
  `search` varchar(255) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `raw_html` text DEFAULT NULL,
  `is_imported` tinyint(1) NOT NULL DEFAULT 0,
  `import_hash` varchar(64) DEFAULT NULL,
  `status` enum('draft','published','expired','archived') NOT NULL DEFAULT 'published',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jobs_import_hash_unique` (`import_hash`),
  UNIQUE KEY `jobs_source_source_url_unique` (`source`,`source_url`),
  KEY `jobs_title_index` (`title`),
  KEY `jobs_company_index` (`company`),
  KEY `jobs_location_index` (`location`),
  KEY `jobs_is_wfh_index` (`is_wfh`),
  KEY `jobs_search_index` (`search`),
  KEY `jobs_status_index` (`status`),
  KEY `jobs_expires_at_index` (`expires_at`),
  KEY `jobs_source_index` (`source`),
  FULLTEXT KEY `fulltext_idx` (`title`,`description`,`search`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES
(1,'Night Audit Clerk 02:13','Additional Information Incentive Plans Offered, Overnight Shift, Weekends availability, Great Benefits, Free Meal, Free Parking Job Number 25173285 Job Category Finance & Accounting Location Sheraton Overland Park Hotel at the Convention Center, 6100 College Boulevard, Overland Park, Kansas, United States, 66211 VIEW ON MAP Schedule Full Time Located Remotely? N Position Type Non-Management Pay Range: $18.50-$18.50 per hour POSITION SUMMARY Complete end-of-day activities including posting charg… 02:13','Marriott International, Inc','Leawood, Johnson County',NULL,0,NULL,'https://www.adzuna.com/land/ad/5468482771?se=fnw9Ae258BGhl_-Iy59c6Q&utm_medium=api&utm_source=904ab51a&v=D211DF3C3362FDE5BAAE3F970495DE9C93EC35CD',NULL,0,NULL,'draft','2025-12-20 02:13:24','2025-11-05 02:13:24','2025-11-05 02:13:24',NULL),
(2,'File Clerk 02:13','This is a remote position. We are currently seeking an Entry-Level File Clerk to join our team. As a File Clerk, you will play a crucial role in organizing and maintaining physical and digital files, ensuring efficient document management processes. This position is vital to the company\'s operations, as it directly impacts the accessibility and accuracy of information for various projects and departments. You will be involved in handling a wide range of files related to photography projects, cl… 02:13','Benefits in a Card','Batesville, Greenville County',NULL,0,NULL,'https://www.adzuna.com/details/5479546654?utm_medium=api&utm_source=904ab51a',NULL,0,NULL,'draft','2025-12-20 02:13:24','2025-11-05 02:13:24','2025-11-05 02:13:24',NULL),
(3,'File Clerk 02:13','This is a remote position. We are looking to hire a conscientious file clerk to ensure our organization\'s records are correctly sequenced and filed, and to capture tracking information in electronic databases. The file clerk gathers documentation from internal departments, and codes material chronologically, numerically, alphabetically, and by subject matter. You will store hard copies of documents such as invoices, receipts and forms, and create new files. You will retrieve information on requ… 02:13','Melinda Instal','San Diego, San Diego County',NULL,0,NULL,'https://www.adzuna.com/details/5466570847?utm_medium=api&utm_source=904ab51a',NULL,0,NULL,'draft','2025-12-20 02:13:24','2025-11-05 02:13:24','2025-11-05 02:13:24',NULL);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-05  4:24:53
