-- MySQL dump 10.16  Distrib 10.1.26-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: invoicereminder
-- ------------------------------------------------------
-- Server version	10.1.26-MariaDB-0+deb9u1

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

CREATE DATABASE IF NOT EXISTS `invoicereminder`;

USE `invoicereminder`

--
-- Table structure for table `invoicereminder_balance`
--

DROP TABLE IF EXISTS `invoicereminder_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoicereminder_balance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_debtors` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `payment` float NOT NULL DEFAULT '0',
  `amount` float NOT NULL DEFAULT '0',
  `cost` float NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `happened` datetime NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoicereminder_debtors`
--

DROP TABLE IF EXISTS `invoicereminder_debtors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoicereminder_debtors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `invoicenumber` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `address` tinytext NOT NULL,
  `zipcode` tinytext NOT NULL,
  `city` tinytext NOT NULL,
  `orgno` tinytext NOT NULL,
  `amount` float NOT NULL,
  `collectioncost` float NOT NULL DEFAULT '0',
  `remindercost` float NOT NULL DEFAULT '0',
  `percentage` float NOT NULL,
  `email` tinytext NOT NULL,
  `email_bcc` tinytext NOT NULL,
  `invoicedate` date NOT NULL,
  `duedate` date NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `mails_sent` int(11) NOT NULL,
  `last_reminder` datetime NOT NULL,
  `reminder_days` int(11) NOT NULL DEFAULT '30',
  `day_of_month` int(11) NOT NULL DEFAULT '0',
  `template` tinytext NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoicereminder_log`
--

DROP TABLE IF EXISTS `invoicereminder_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoicereminder_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_debtors` int(11) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `type` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=288 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoicereminder_properties`
--

DROP TABLE IF EXISTS `invoicereminder_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoicereminder_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property` tinytext NOT NULL,
  `value` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoicereminder_riksbank_reference_rate`
--

DROP TABLE IF EXISTS `invoicereminder_riksbank_reference_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoicereminder_riksbank_reference_rate` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `updated` date NOT NULL,
  `rate` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-08-08 17:22:40
