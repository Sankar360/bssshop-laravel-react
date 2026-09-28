-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: bssshop_laravel
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Table structure for table `blog`
--

DROP TABLE IF EXISTS `blog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `views` int NOT NULL DEFAULT '0',
  `published_at` datetime DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_keywords` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog`
--

LOCK TABLES `blog` WRITE;
/*!40000 ALTER TABLE `blog` DISABLE KEYS */;
INSERT INTO `blog` VALUES (1,'Best Cameras for Beginners in 2026','best-cameras-for-beginners-in-2026','Cameras','Choosing the right camera can be difficult for beginners.\r\nIn this guide, we explain the important features to consider\r\nwhen buying a camera, including image quality, lens options,\r\nbattery life, autofocus, and portability.','A simple guide to choosing the right camera for beginners.','blog/3ZJbwjOA9iHXUgHjNaLdgwh92bt42WYdgOsAzBkl.jpg','Admin','published',9,'2026-08-01 13:36:09','Best Cameras for Beginners in 2026','Discover the best cameras for beginners and learn what features\r\nto consider before buying a camera.','best camera for beginners, cameras 2026, digital camera, photography camera, beginner camera','2026-08-01 12:34:11','2026-09-25 08:58:57'),(2,'How to Choose the Perfect Chair for Your Home','how-to-choose-the-perfect-chair-for-your-home','Chairs','Choosing the right chair depends on comfort, design, size, and\r\nthe space where you plan to use it. A good chair should provide\r\nproper support while matching the style of your room.','Learn how to choose a comfortable and stylish chair for your home.','blog/DTEb4vzlwNt1mEqndl9MLznF74FGBtvYnDdKg5cl.jpg','Admin','published',10,'2026-09-18 12:40:47','How to Choose the Perfect Chair for Your Home','Discover how to choose the right chair based on comfort, design,\r\nsize, support, and your home interior.','chairs, best chairs, comfortable chairs, home chairs, office chairs, furniture','2026-09-18 12:40:47','2026-09-26 09:11:50');
/*!40000 ALTER TABLE `blog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-featured_products_data_v2','a:12:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:29;s:4:\"name\";s:22:\"Coffee Machine Premium\";s:4:\"slug\";s:22:\"coffee-machine-premium\";s:11:\"description\";s:43:\"Automatic coffee maker for home and office.\";s:5:\"price\";s:8:\"15000.00\";s:5:\"image\";s:52:\"uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";s:5:\"stock\";i:25;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:22;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:07.000000Z\";s:17:\"short_description\";s:26:\"Automatic espresso machine\";s:10:\"sale_price\";s:8:\"14450.00\";s:3:\"sku\";s:9:\"SKU-CF009\";s:4:\"tags\";s:22:\"coffee,kitchen,machine\";s:6:\"weight\";s:4:\"4.50\";s:6:\"length\";s:5:\"40.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"30.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:29;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";}i:7;a:28:{s:2:\"id\";i:30;s:4:\"name\";s:19:\"Modern Office Chair\";s:4:\"slug\";s:19:\"modern-office-chair\";s:11:\"description\";s:44:\"Ergonomic office chair with premium support.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";s:5:\"stock\";i:35;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:13;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:37.000000Z\";s:17:\"short_description\";s:23:\"Comfort workspace chair\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-CH010\";s:4:\"tags\";s:17:\"chair,office,home\";s:6:\"weight\";s:4:\"8.00\";s:6:\"length\";s:5:\"70.00\";s:5:\"width\";s:5:\"70.00\";s:6:\"height\";s:6:\"120.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:30;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:32;s:4:\"name\";s:18:\"Digital Camera Pro\";s:4:\"slug\";s:18:\"digital-camera-pro\";s:11:\"description\";s:43:\"Professional camera for photography lovers.\";s:5:\"price\";s:9:\"110000.00\";s:5:\"image\";s:52:\"uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";s:5:\"stock\";i:15;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:32;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:56.000000Z\";s:17:\"short_description\";s:30:\"High resolution digital camera\";s:10:\"sale_price\";s:8:\"99000.00\";s:3:\"sku\";s:9:\"SKU-CM012\";s:4:\"tags\";s:17:\"camera,photo,dslr\";s:6:\"weight\";s:4:\"1.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"12.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:32;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";}i:10;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:11;a:28:{s:2:\"id\";i:39;s:4:\"name\";s:16:\"Air Purifier Pro\";s:4:\"slug\";s:16:\"air-purifier-pro\";s:11:\"description\";s:40:\"Smart air purifier for clean indoor air.\";s:5:\"price\";s:6:\"280.00\";s:5:\"image\";s:52:\"uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:23;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:46:49.000000Z\";s:17:\"short_description\";s:22:\"Smart clean air system\";s:10:\"sale_price\";s:6:\"230.00\";s:3:\"sku\";s:9:\"SKU-AP019\";s:4:\"tags\";s:18:\"air,purifier,smart\";s:6:\"weight\";s:4:\"5.00\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"30.00\";s:6:\"height\";s:5:\"50.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:39;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";}}',1790573496),('laravel-cache-home_banner_data','a:11:{s:2:\"id\";i:1;s:10:\"badge_text\";s:18:\"Premium Collection\";s:11:\"title_line1\";s:9:\"Shop with\";s:11:\"title_line2\";s:16:\"Style & Elegance\";s:8:\"subtitle\";s:51:\"Discover our curated collection of premium products\";s:11:\"button_text\";s:20:\"Explore All Products\";s:11:\"button_link\";s:9:\"#products\";s:11:\"button_icon\";s:14:\"bi-arrow-right\";s:9:\"is_active\";b:1;s:10:\"created_at\";s:27:\"2026-08-05T09:43:51.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-05T09:43:51.000000Z\";}',1790576796),('laravel-cache-home_categories_data','a:9:{i:0;a:7:{s:2:\"id\";i:1;s:9:\"parent_id\";N;s:4:\"name\";s:11:\"Electronics\";s:4:\"slug\";s:11:\"electronics\";s:4:\"icon\";s:9:\"bi-laptop\";s:10:\"item_count\";i:245;s:4:\"link\";s:21:\"/category/electronics\";}i:1;a:7:{s:2:\"id\";i:2;s:9:\"parent_id\";N;s:4:\"name\";s:23:\"Computers & Accessories\";s:4:\"slug\";s:21:\"computers-accessories\";s:4:\"icon\";s:6:\"bi-bag\";s:10:\"item_count\";i:189;s:4:\"link\";s:31:\"/category/computers-accessories\";}i:2;a:7:{s:2:\"id\";i:3;s:9:\"parent_id\";N;s:4:\"name\";s:7:\"Fashion\";s:4:\"slug\";s:7:\"fashion\";s:4:\"icon\";s:8:\"bi-house\";s:10:\"item_count\";i:134;s:4:\"link\";s:17:\"/category/fashion\";}i:3;a:7:{s:2:\"id\";i:4;s:9:\"parent_id\";N;s:4:\"name\";s:15:\"Home Appliances\";s:4:\"slug\";s:15:\"home-appliances\";s:4:\"icon\";s:10:\"bi-flower1\";s:10:\"item_count\";i:156;s:4:\"link\";s:25:\"/category/home-appliances\";}i:4;a:7:{s:2:\"id\";i:5;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Furniture\";s:4:\"slug\";s:9:\"furniture\";s:4:\"icon\";s:10:\"bi-bicycle\";s:10:\"item_count\";i:87;s:4:\"link\";s:19:\"/category/furniture\";}i:5;a:7:{s:2:\"id\";i:28;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Travel\";s:4:\"slug\";s:6:\"travel\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/travel\";}i:6;a:7:{s:2:\"id\";i:29;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Gaming\";s:4:\"slug\";s:6:\"gaming\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/gaming\";}i:7;a:7:{s:2:\"id\";i:30;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Wearables\";s:4:\"slug\";s:9:\"wearables\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:19:\"/category/wearables\";}i:8;a:7:{s:2:\"id\";i:31;s:9:\"parent_id\";N;s:4:\"name\";s:18:\"Mobile Accessories\";s:4:\"slug\";s:18:\"mobile-accessories\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:28:\"/category/mobile-accessories\";}}',1790576796),('laravel-cache-home_page_data_en-US,en;q=0.9','a:5:{s:6:\"banner\";a:11:{s:2:\"id\";i:1;s:10:\"badge_text\";s:18:\"Premium Collection\";s:11:\"title_line1\";s:9:\"Shop with\";s:11:\"title_line2\";s:16:\"Style & Elegance\";s:8:\"subtitle\";s:51:\"Discover our curated collection of premium products\";s:11:\"button_text\";s:20:\"Explore All Products\";s:11:\"button_link\";s:9:\"#products\";s:11:\"button_icon\";s:14:\"bi-arrow-right\";s:9:\"is_active\";b:1;s:10:\"created_at\";s:27:\"2026-08-05T09:43:51.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-05T09:43:51.000000Z\";}s:10:\"categories\";a:9:{i:0;a:7:{s:2:\"id\";i:1;s:9:\"parent_id\";N;s:4:\"name\";s:11:\"Electronics\";s:4:\"slug\";s:11:\"electronics\";s:4:\"icon\";s:9:\"bi-laptop\";s:10:\"item_count\";i:245;s:4:\"link\";s:21:\"/category/electronics\";}i:1;a:7:{s:2:\"id\";i:2;s:9:\"parent_id\";N;s:4:\"name\";s:23:\"Computers & Accessories\";s:4:\"slug\";s:21:\"computers-accessories\";s:4:\"icon\";s:6:\"bi-bag\";s:10:\"item_count\";i:189;s:4:\"link\";s:31:\"/category/computers-accessories\";}i:2;a:7:{s:2:\"id\";i:3;s:9:\"parent_id\";N;s:4:\"name\";s:7:\"Fashion\";s:4:\"slug\";s:7:\"fashion\";s:4:\"icon\";s:8:\"bi-house\";s:10:\"item_count\";i:134;s:4:\"link\";s:17:\"/category/fashion\";}i:3;a:7:{s:2:\"id\";i:4;s:9:\"parent_id\";N;s:4:\"name\";s:15:\"Home Appliances\";s:4:\"slug\";s:15:\"home-appliances\";s:4:\"icon\";s:10:\"bi-flower1\";s:10:\"item_count\";i:156;s:4:\"link\";s:25:\"/category/home-appliances\";}i:4;a:7:{s:2:\"id\";i:5;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Furniture\";s:4:\"slug\";s:9:\"furniture\";s:4:\"icon\";s:10:\"bi-bicycle\";s:10:\"item_count\";i:87;s:4:\"link\";s:19:\"/category/furniture\";}i:5;a:7:{s:2:\"id\";i:28;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Travel\";s:4:\"slug\";s:6:\"travel\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/travel\";}i:6;a:7:{s:2:\"id\";i:29;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Gaming\";s:4:\"slug\";s:6:\"gaming\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/gaming\";}i:7;a:7:{s:2:\"id\";i:30;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Wearables\";s:4:\"slug\";s:9:\"wearables\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:19:\"/category/wearables\";}i:8;a:7:{s:2:\"id\";i:31;s:9:\"parent_id\";N;s:4:\"name\";s:18:\"Mobile Accessories\";s:4:\"slug\";s:18:\"mobile-accessories\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:28:\"/category/mobile-accessories\";}}s:13:\"home_products\";a:11:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:27;s:4:\"name\";s:23:\"Mechanical Keyboard RGB\";s:4:\"slug\";s:23:\"mechanical-keyboard-rgb\";s:11:\"description\";s:35:\"RGB mechanical keyboard for gamers.\";s:5:\"price\";s:7:\"1000.00\";s:5:\"image\";s:52:\"uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";s:5:\"stock\";i:80;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:17;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"5.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:40:41.000000Z\";s:17:\"short_description\";s:19:\"RGB gaming keyboard\";s:10:\"sale_price\";s:6:\"950.00\";s:3:\"sku\";s:9:\"SKU-KB007\";s:4:\"tags\";s:19:\"keyboard,gaming,rgb\";s:6:\"weight\";s:4:\"0.90\";s:6:\"length\";s:5:\"45.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:4:\"5.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:27;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";}i:7;a:28:{s:2:\"id\";i:28;s:4:\"name\";s:14:\"Wireless Mouse\";s:4:\"slug\";s:14:\"wireless-mouse\";s:11:\"description\";s:44:\"Ergonomic wireless mouse with fast response.\";s:5:\"price\";s:6:\"250.00\";s:5:\"image\";s:52:\"uploads/products/1786979391_04bb43e34683288caf04.jpg\";s:5:\"stock\";i:150;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:34;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:41:27.000000Z\";s:17:\"short_description\";s:22:\"Comfort wireless mouse\";s:10:\"sale_price\";s:6:\"225.00\";s:3:\"sku\";s:9:\"SKU-MS008\";s:4:\"tags\";s:23:\"mouse,wireless,computer\";s:6:\"weight\";s:4:\"0.10\";s:6:\"length\";s:5:\"12.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"4.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:28;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979391_04bb43e34683288caf04.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:34;s:4:\"name\";s:18:\"Smart Home Speaker\";s:4:\"slug\";s:18:\"smart-home-speaker\";s:11:\"description\";s:30:\"Voice assistant smart speaker.\";s:5:\"price\";s:6:\"500.00\";s:5:\"image\";s:52:\"uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";s:5:\"stock\";i:60;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:33;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:44:10.000000Z\";s:17:\"short_description\";s:19:\"Smart voice speaker\";s:10:\"sale_price\";s:6:\"450.00\";s:3:\"sku\";s:9:\"SKU-SP014\";s:4:\"tags\";s:18:\"speaker,smart,home\";s:6:\"weight\";s:4:\"1.00\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"20.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:34;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";}i:10;a:28:{s:2:\"id\";i:40;s:4:\"name\";s:19:\"Portable Power Bank\";s:4:\"slug\";s:19:\"portable-power-bank\";s:11:\"description\";s:45:\"Fast charging power bank with large capacity.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";s:5:\"stock\";i:200;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:31;s:14:\"subcategory_id\";i:39;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:50:39.000000Z\";s:17:\"short_description\";s:26:\"Fast charging battery pack\";s:10:\"sale_price\";s:7:\"1400.00\";s:3:\"sku\";s:9:\"SKU-PB020\";s:4:\"tags\";s:21:\"battery,charger,power\";s:6:\"weight\";s:4:\"0.25\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:40;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";}}s:17:\"featured_products\";a:12:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:29;s:4:\"name\";s:22:\"Coffee Machine Premium\";s:4:\"slug\";s:22:\"coffee-machine-premium\";s:11:\"description\";s:43:\"Automatic coffee maker for home and office.\";s:5:\"price\";s:8:\"15000.00\";s:5:\"image\";s:52:\"uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";s:5:\"stock\";i:25;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:22;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:07.000000Z\";s:17:\"short_description\";s:26:\"Automatic espresso machine\";s:10:\"sale_price\";s:8:\"14450.00\";s:3:\"sku\";s:9:\"SKU-CF009\";s:4:\"tags\";s:22:\"coffee,kitchen,machine\";s:6:\"weight\";s:4:\"4.50\";s:6:\"length\";s:5:\"40.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"30.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:29;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";}i:7;a:28:{s:2:\"id\";i:30;s:4:\"name\";s:19:\"Modern Office Chair\";s:4:\"slug\";s:19:\"modern-office-chair\";s:11:\"description\";s:44:\"Ergonomic office chair with premium support.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";s:5:\"stock\";i:35;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:13;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:37.000000Z\";s:17:\"short_description\";s:23:\"Comfort workspace chair\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-CH010\";s:4:\"tags\";s:17:\"chair,office,home\";s:6:\"weight\";s:4:\"8.00\";s:6:\"length\";s:5:\"70.00\";s:5:\"width\";s:5:\"70.00\";s:6:\"height\";s:6:\"120.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:30;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:32;s:4:\"name\";s:18:\"Digital Camera Pro\";s:4:\"slug\";s:18:\"digital-camera-pro\";s:11:\"description\";s:43:\"Professional camera for photography lovers.\";s:5:\"price\";s:9:\"110000.00\";s:5:\"image\";s:52:\"uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";s:5:\"stock\";i:15;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:32;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:56.000000Z\";s:17:\"short_description\";s:30:\"High resolution digital camera\";s:10:\"sale_price\";s:8:\"99000.00\";s:3:\"sku\";s:9:\"SKU-CM012\";s:4:\"tags\";s:17:\"camera,photo,dslr\";s:6:\"weight\";s:4:\"1.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"12.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:32;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";}i:10;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:11;a:28:{s:2:\"id\";i:39;s:4:\"name\";s:16:\"Air Purifier Pro\";s:4:\"slug\";s:16:\"air-purifier-pro\";s:11:\"description\";s:40:\"Smart air purifier for clean indoor air.\";s:5:\"price\";s:6:\"280.00\";s:5:\"image\";s:52:\"uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:23;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:46:49.000000Z\";s:17:\"short_description\";s:22:\"Smart clean air system\";s:10:\"sale_price\";s:6:\"230.00\";s:3:\"sku\";s:9:\"SKU-AP019\";s:4:\"tags\";s:18:\"air,purifier,smart\";s:6:\"weight\";s:4:\"5.00\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"30.00\";s:6:\"height\";s:5:\"50.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:39;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";}}s:15:\"random_products\";a:8:{i:0;a:28:{s:2:\"id\";i:32;s:4:\"name\";s:18:\"Digital Camera Pro\";s:4:\"slug\";s:18:\"digital-camera-pro\";s:11:\"description\";s:43:\"Professional camera for photography lovers.\";s:5:\"price\";s:9:\"110000.00\";s:5:\"image\";s:52:\"uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";s:5:\"stock\";i:15;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:32;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:56.000000Z\";s:17:\"short_description\";s:30:\"High resolution digital camera\";s:10:\"sale_price\";s:8:\"99000.00\";s:3:\"sku\";s:9:\"SKU-CM012\";s:4:\"tags\";s:17:\"camera,photo,dslr\";s:6:\"weight\";s:4:\"1.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"12.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:32;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";}i:1;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:2;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:3;a:28:{s:2:\"id\";i:38;s:4:\"name\";s:18:\"Premium Sunglasses\";s:4:\"slug\";s:18:\"premium-sunglasses\";s:11:\"description\";s:38:\"Stylish sunglasses with UV protection.\";s:5:\"price\";s:6:\"150.00\";s:5:\"image\";s:52:\"uploads/products/1786979770_3fea42e7ab633822905a.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:21;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"13.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:54.000000Z\";s:17:\"short_description\";s:18:\"Fashion sunglasses\";s:10:\"sale_price\";s:6:\"130.00\";s:3:\"sku\";s:9:\"SKU-SG018\";s:4:\"tags\";s:18:\"glasses,fashion,uv\";s:6:\"weight\";s:4:\"0.05\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"5.00\";s:6:\"height\";s:4:\"5.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:0;s:10:\"product_id\";i:38;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979770_3fea42e7ab633822905a.jpg\";}i:4;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:5;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:6;a:28:{s:2:\"id\";i:28;s:4:\"name\";s:14:\"Wireless Mouse\";s:4:\"slug\";s:14:\"wireless-mouse\";s:11:\"description\";s:44:\"Ergonomic wireless mouse with fast response.\";s:5:\"price\";s:6:\"250.00\";s:5:\"image\";s:52:\"uploads/products/1786979391_04bb43e34683288caf04.jpg\";s:5:\"stock\";i:150;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:34;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:41:27.000000Z\";s:17:\"short_description\";s:22:\"Comfort wireless mouse\";s:10:\"sale_price\";s:6:\"225.00\";s:3:\"sku\";s:9:\"SKU-MS008\";s:4:\"tags\";s:23:\"mouse,wireless,computer\";s:6:\"weight\";s:4:\"0.10\";s:6:\"length\";s:5:\"12.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"4.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:28;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979391_04bb43e34683288caf04.jpg\";}i:7;a:28:{s:2:\"id\";i:36;s:4:\"name\";s:20:\"Fitness Tracker Band\";s:4:\"slug\";s:20:\"fitness-tracker-band\";s:11:\"description\";s:34:\"Track steps, heart rate and sleep.\";s:5:\"price\";s:7:\"1150.00\";s:5:\"image\";s:52:\"uploads/products/1786979709_8cb0eb6143c3f64ed90c.jpg\";s:5:\"stock\";i:110;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:30;s:14:\"subcategory_id\";i:38;s:6:\"rating\";s:4:\"0.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:44:46.000000Z\";s:17:\"short_description\";s:20:\"Health tracking band\";s:10:\"sale_price\";s:7:\"1100.00\";s:3:\"sku\";s:9:\"SKU-FB016\";s:4:\"tags\";s:19:\"fitness,band,health\";s:6:\"weight\";s:4:\"0.05\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"3.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:0;s:10:\"product_id\";i:36;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979709_8cb0eb6143c3f64ed90c.jpg\";}}}',1790331324),('laravel-cache-home_page_data_en-US,en;q=0.9,ta-IN;q=0.8,ta;q=0.7','a:5:{s:6:\"banner\";a:11:{s:2:\"id\";i:1;s:10:\"badge_text\";s:18:\"Premium Collection\";s:11:\"title_line1\";s:9:\"Shop with\";s:11:\"title_line2\";s:16:\"Style & Elegance\";s:8:\"subtitle\";s:51:\"Discover our curated collection of premium products\";s:11:\"button_text\";s:20:\"Explore All Products\";s:11:\"button_link\";s:9:\"#products\";s:11:\"button_icon\";s:14:\"bi-arrow-right\";s:9:\"is_active\";b:1;s:10:\"created_at\";s:27:\"2026-08-05T09:43:51.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-05T09:43:51.000000Z\";}s:10:\"categories\";a:9:{i:0;a:7:{s:2:\"id\";i:1;s:9:\"parent_id\";N;s:4:\"name\";s:11:\"Electronics\";s:4:\"slug\";s:11:\"electronics\";s:4:\"icon\";s:9:\"bi-laptop\";s:10:\"item_count\";i:245;s:4:\"link\";s:21:\"/category/electronics\";}i:1;a:7:{s:2:\"id\";i:2;s:9:\"parent_id\";N;s:4:\"name\";s:23:\"Computers & Accessories\";s:4:\"slug\";s:21:\"computers-accessories\";s:4:\"icon\";s:6:\"bi-bag\";s:10:\"item_count\";i:189;s:4:\"link\";s:31:\"/category/computers-accessories\";}i:2;a:7:{s:2:\"id\";i:3;s:9:\"parent_id\";N;s:4:\"name\";s:7:\"Fashion\";s:4:\"slug\";s:7:\"fashion\";s:4:\"icon\";s:8:\"bi-house\";s:10:\"item_count\";i:134;s:4:\"link\";s:17:\"/category/fashion\";}i:3;a:7:{s:2:\"id\";i:4;s:9:\"parent_id\";N;s:4:\"name\";s:15:\"Home Appliances\";s:4:\"slug\";s:15:\"home-appliances\";s:4:\"icon\";s:10:\"bi-flower1\";s:10:\"item_count\";i:156;s:4:\"link\";s:25:\"/category/home-appliances\";}i:4;a:7:{s:2:\"id\";i:5;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Furniture\";s:4:\"slug\";s:9:\"furniture\";s:4:\"icon\";s:10:\"bi-bicycle\";s:10:\"item_count\";i:87;s:4:\"link\";s:19:\"/category/furniture\";}i:5;a:7:{s:2:\"id\";i:28;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Travel\";s:4:\"slug\";s:6:\"travel\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/travel\";}i:6;a:7:{s:2:\"id\";i:29;s:9:\"parent_id\";N;s:4:\"name\";s:6:\"Gaming\";s:4:\"slug\";s:6:\"gaming\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:16:\"/category/gaming\";}i:7;a:7:{s:2:\"id\";i:30;s:9:\"parent_id\";N;s:4:\"name\";s:9:\"Wearables\";s:4:\"slug\";s:9:\"wearables\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:19:\"/category/wearables\";}i:8;a:7:{s:2:\"id\";i:31;s:9:\"parent_id\";N;s:4:\"name\";s:18:\"Mobile Accessories\";s:4:\"slug\";s:18:\"mobile-accessories\";s:4:\"icon\";s:6:\"bi-box\";s:10:\"item_count\";i:0;s:4:\"link\";s:28:\"/category/mobile-accessories\";}}s:13:\"home_products\";a:11:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:27;s:4:\"name\";s:23:\"Mechanical Keyboard RGB\";s:4:\"slug\";s:23:\"mechanical-keyboard-rgb\";s:11:\"description\";s:35:\"RGB mechanical keyboard for gamers.\";s:5:\"price\";s:7:\"1000.00\";s:5:\"image\";s:52:\"uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";s:5:\"stock\";i:80;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:17;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"5.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:40:41.000000Z\";s:17:\"short_description\";s:19:\"RGB gaming keyboard\";s:10:\"sale_price\";s:6:\"950.00\";s:3:\"sku\";s:9:\"SKU-KB007\";s:4:\"tags\";s:19:\"keyboard,gaming,rgb\";s:6:\"weight\";s:4:\"0.90\";s:6:\"length\";s:5:\"45.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:4:\"5.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:27;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";}i:7;a:28:{s:2:\"id\";i:28;s:4:\"name\";s:14:\"Wireless Mouse\";s:4:\"slug\";s:14:\"wireless-mouse\";s:11:\"description\";s:44:\"Ergonomic wireless mouse with fast response.\";s:5:\"price\";s:6:\"250.00\";s:5:\"image\";s:52:\"uploads/products/1786979391_04bb43e34683288caf04.jpg\";s:5:\"stock\";i:150;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:34;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:41:27.000000Z\";s:17:\"short_description\";s:22:\"Comfort wireless mouse\";s:10:\"sale_price\";s:6:\"225.00\";s:3:\"sku\";s:9:\"SKU-MS008\";s:4:\"tags\";s:23:\"mouse,wireless,computer\";s:6:\"weight\";s:4:\"0.10\";s:6:\"length\";s:5:\"12.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"4.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:28;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979391_04bb43e34683288caf04.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:34;s:4:\"name\";s:18:\"Smart Home Speaker\";s:4:\"slug\";s:18:\"smart-home-speaker\";s:11:\"description\";s:30:\"Voice assistant smart speaker.\";s:5:\"price\";s:6:\"500.00\";s:5:\"image\";s:52:\"uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";s:5:\"stock\";i:60;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:33;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:44:10.000000Z\";s:17:\"short_description\";s:19:\"Smart voice speaker\";s:10:\"sale_price\";s:6:\"450.00\";s:3:\"sku\";s:9:\"SKU-SP014\";s:4:\"tags\";s:18:\"speaker,smart,home\";s:6:\"weight\";s:4:\"1.00\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"20.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:34;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";}i:10;a:28:{s:2:\"id\";i:40;s:4:\"name\";s:19:\"Portable Power Bank\";s:4:\"slug\";s:19:\"portable-power-bank\";s:11:\"description\";s:45:\"Fast charging power bank with large capacity.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";s:5:\"stock\";i:200;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:31;s:14:\"subcategory_id\";i:39;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:50:39.000000Z\";s:17:\"short_description\";s:26:\"Fast charging battery pack\";s:10:\"sale_price\";s:7:\"1400.00\";s:3:\"sku\";s:9:\"SKU-PB020\";s:4:\"tags\";s:21:\"battery,charger,power\";s:6:\"weight\";s:4:\"0.25\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:40;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";}}s:17:\"featured_products\";a:12:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:29;s:4:\"name\";s:22:\"Coffee Machine Premium\";s:4:\"slug\";s:22:\"coffee-machine-premium\";s:11:\"description\";s:43:\"Automatic coffee maker for home and office.\";s:5:\"price\";s:8:\"15000.00\";s:5:\"image\";s:52:\"uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";s:5:\"stock\";i:25;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:22;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:07.000000Z\";s:17:\"short_description\";s:26:\"Automatic espresso machine\";s:10:\"sale_price\";s:8:\"14450.00\";s:3:\"sku\";s:9:\"SKU-CF009\";s:4:\"tags\";s:22:\"coffee,kitchen,machine\";s:6:\"weight\";s:4:\"4.50\";s:6:\"length\";s:5:\"40.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"30.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:29;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979421_bfda7c631047caf1a3ae.jpg\";}i:7;a:28:{s:2:\"id\";i:30;s:4:\"name\";s:19:\"Modern Office Chair\";s:4:\"slug\";s:19:\"modern-office-chair\";s:11:\"description\";s:44:\"Ergonomic office chair with premium support.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";s:5:\"stock\";i:35;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:13;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:37.000000Z\";s:17:\"short_description\";s:23:\"Comfort workspace chair\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-CH010\";s:4:\"tags\";s:17:\"chair,office,home\";s:6:\"weight\";s:4:\"8.00\";s:6:\"length\";s:5:\"70.00\";s:5:\"width\";s:5:\"70.00\";s:6:\"height\";s:6:\"120.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:30;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:32;s:4:\"name\";s:18:\"Digital Camera Pro\";s:4:\"slug\";s:18:\"digital-camera-pro\";s:11:\"description\";s:43:\"Professional camera for photography lovers.\";s:5:\"price\";s:9:\"110000.00\";s:5:\"image\";s:52:\"uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";s:5:\"stock\";i:15;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:32;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:56.000000Z\";s:17:\"short_description\";s:30:\"High resolution digital camera\";s:10:\"sale_price\";s:8:\"99000.00\";s:3:\"sku\";s:9:\"SKU-CM012\";s:4:\"tags\";s:17:\"camera,photo,dslr\";s:6:\"weight\";s:4:\"1.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"12.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:32;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979526_44a3751eb5ac9776f084.jpg\";}i:10;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:11;a:28:{s:2:\"id\";i:39;s:4:\"name\";s:16:\"Air Purifier Pro\";s:4:\"slug\";s:16:\"air-purifier-pro\";s:11:\"description\";s:40:\"Smart air purifier for clean indoor air.\";s:5:\"price\";s:6:\"280.00\";s:5:\"image\";s:52:\"uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:23;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:46:49.000000Z\";s:17:\"short_description\";s:22:\"Smart clean air system\";s:10:\"sale_price\";s:6:\"230.00\";s:3:\"sku\";s:9:\"SKU-AP019\";s:4:\"tags\";s:18:\"air,purifier,smart\";s:6:\"weight\";s:4:\"5.00\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"30.00\";s:6:\"height\";s:5:\"50.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:39;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";}}s:15:\"random_products\";a:8:{i:0;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:1;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:30;s:4:\"name\";s:19:\"Modern Office Chair\";s:4:\"slug\";s:19:\"modern-office-chair\";s:11:\"description\";s:44:\"Ergonomic office chair with premium support.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";s:5:\"stock\";i:35;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:13;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:37.000000Z\";s:17:\"short_description\";s:23:\"Comfort workspace chair\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-CH010\";s:4:\"tags\";s:17:\"chair,office,home\";s:6:\"weight\";s:4:\"8.00\";s:6:\"length\";s:5:\"70.00\";s:5:\"width\";s:5:\"70.00\";s:6:\"height\";s:6:\"120.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:30;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";}i:4;a:28:{s:2:\"id\";i:39;s:4:\"name\";s:16:\"Air Purifier Pro\";s:4:\"slug\";s:16:\"air-purifier-pro\";s:11:\"description\";s:40:\"Smart air purifier for clean indoor air.\";s:5:\"price\";s:6:\"280.00\";s:5:\"image\";s:52:\"uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:23;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:46:49.000000Z\";s:17:\"short_description\";s:22:\"Smart clean air system\";s:10:\"sale_price\";s:6:\"230.00\";s:3:\"sku\";s:9:\"SKU-AP019\";s:4:\"tags\";s:18:\"air,purifier,smart\";s:6:\"weight\";s:4:\"5.00\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"30.00\";s:6:\"height\";s:5:\"50.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:39;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:7;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}}}',1790573796),('laravel-cache-home_products_data_v2','a:11:{i:0;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:1;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:24;s:4:\"name\";s:18:\"Luxury Leather Bag\";s:4:\"slug\";s:18:\"luxury-leather-bag\";s:11:\"description\";s:45:\"Premium leather handbag for modern lifestyle.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:20;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:49:10.000000Z\";s:17:\"short_description\";s:27:\"Elegant leather fashion bag\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-BG004\";s:4:\"tags\";s:19:\"bag,leather,fashion\";s:6:\"weight\";s:4:\"0.80\";s:6:\"length\";s:5:\"35.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:24;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979249_64a2a8872a76d1de19ec.jpg\";}i:4;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:27;s:4:\"name\";s:23:\"Mechanical Keyboard RGB\";s:4:\"slug\";s:23:\"mechanical-keyboard-rgb\";s:11:\"description\";s:35:\"RGB mechanical keyboard for gamers.\";s:5:\"price\";s:7:\"1000.00\";s:5:\"image\";s:52:\"uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";s:5:\"stock\";i:80;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:17;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"5.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:40:41.000000Z\";s:17:\"short_description\";s:19:\"RGB gaming keyboard\";s:10:\"sale_price\";s:6:\"950.00\";s:3:\"sku\";s:9:\"SKU-KB007\";s:4:\"tags\";s:19:\"keyboard,gaming,rgb\";s:6:\"weight\";s:4:\"0.90\";s:6:\"length\";s:5:\"45.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:4:\"5.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:27;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg\";}i:7;a:28:{s:2:\"id\";i:28;s:4:\"name\";s:14:\"Wireless Mouse\";s:4:\"slug\";s:14:\"wireless-mouse\";s:11:\"description\";s:44:\"Ergonomic wireless mouse with fast response.\";s:5:\"price\";s:6:\"250.00\";s:5:\"image\";s:52:\"uploads/products/1786979391_04bb43e34683288caf04.jpg\";s:5:\"stock\";i:150;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:34;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:41:27.000000Z\";s:17:\"short_description\";s:22:\"Comfort wireless mouse\";s:10:\"sale_price\";s:6:\"225.00\";s:3:\"sku\";s:9:\"SKU-MS008\";s:4:\"tags\";s:23:\"mouse,wireless,computer\";s:6:\"weight\";s:4:\"0.10\";s:6:\"length\";s:5:\"12.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"4.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:28;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979391_04bb43e34683288caf04.jpg\";}i:8;a:28:{s:2:\"id\";i:31;s:4:\"name\";s:20:\"Smart LED TV 55 Inch\";s:4:\"slug\";s:15:\"smart-led-tv-55\";s:11:\"description\";s:43:\"4K smart television with streaming support.\";s:5:\"price\";s:8:\"45000.00\";s:5:\"image\";s:52:\"uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";s:5:\"stock\";i:20;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:12;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:43:05.000000Z\";s:17:\"short_description\";s:23:\"4K smart LED television\";s:10:\"sale_price\";s:8:\"42000.00\";s:3:\"sku\";s:9:\"SKU-TV011\";s:4:\"tags\";s:11:\"tv,smart,4k\";s:6:\"weight\";s:5:\"12.00\";s:6:\"length\";s:6:\"125.00\";s:5:\"width\";s:4:\"8.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:31;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979484_5f8548d7abe9ac742333.jpg\";}i:9;a:28:{s:2:\"id\";i:34;s:4:\"name\";s:18:\"Smart Home Speaker\";s:4:\"slug\";s:18:\"smart-home-speaker\";s:11:\"description\";s:30:\"Voice assistant smart speaker.\";s:5:\"price\";s:6:\"500.00\";s:5:\"image\";s:52:\"uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";s:5:\"stock\";i:60;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:33;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"10.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:44:10.000000Z\";s:17:\"short_description\";s:19:\"Smart voice speaker\";s:10:\"sale_price\";s:6:\"450.00\";s:3:\"sku\";s:9:\"SKU-SP014\";s:4:\"tags\";s:18:\"speaker,smart,home\";s:6:\"weight\";s:4:\"1.00\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"20.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:34;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979620_8063c5003e003cfd6daa.jpg\";}i:10;a:28:{s:2:\"id\";i:40;s:4:\"name\";s:19:\"Portable Power Bank\";s:4:\"slug\";s:19:\"portable-power-bank\";s:11:\"description\";s:45:\"Fast charging power bank with large capacity.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";s:5:\"stock\";i:200;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:31;s:14:\"subcategory_id\";i:39;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"7.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:50:39.000000Z\";s:17:\"short_description\";s:26:\"Fast charging battery pack\";s:10:\"sale_price\";s:7:\"1400.00\";s:3:\"sku\";s:9:\"SKU-PB020\";s:4:\"tags\";s:21:\"battery,charger,power\";s:6:\"weight\";s:4:\"0.25\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:0;s:7:\"is_home\";b:1;s:10:\"product_id\";i:40;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg\";}}',1790573496),('laravel-cache-random_products_data_v2','a:8:{i:0;a:28:{s:2:\"id\";i:37;s:4:\"name\";s:19:\"Wooden Dining Table\";s:4:\"slug\";s:19:\"wooden-dining-table\";s:11:\"description\";s:34:\"Modern wooden dining table design.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979740_043b1e472090147a1a28.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:14;s:6:\"rating\";s:4:\"5.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:45:21.000000Z\";s:17:\"short_description\";s:24:\"Premium dining furniture\";s:10:\"sale_price\";s:8:\"24000.00\";s:3:\"sku\";s:9:\"SKU-TB017\";s:4:\"tags\";s:15:\"table,wood,home\";s:6:\"weight\";s:5:\"25.00\";s:6:\"length\";s:6:\"180.00\";s:5:\"width\";s:5:\"90.00\";s:6:\"height\";s:5:\"75.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:37;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979740_043b1e472090147a1a28.jpg\";}i:1;a:29:{s:2:\"id\";i:21;s:4:\"name\";s:21:\"Premium Smartphone X1\";s:4:\"slug\";s:21:\"premium-smartphone-x1\";s:11:\"description\";s:72:\"Latest flagship smartphone with powerful performance and premium camera.\";s:5:\"price\";s:6:\"100.00\";s:5:\"image\";s:52:\"uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";s:5:\"stock\";i:10;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:9;s:6:\"rating\";s:3:\"4.0\";s:8:\"discount\";s:5:\"20.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-08-17T15:16:40.000000Z\";s:17:\"short_description\";s:30:\"High performance 5G smartphone\";s:10:\"sale_price\";s:5:\"80.00\";s:3:\"sku\";s:2:\"p1\";s:4:\"tags\";s:15:\"phone,5g,mobile\";s:6:\"weight\";s:4:\"0.20\";s:6:\"length\";s:5:\"15.00\";s:5:\"width\";s:4:\"7.00\";s:6:\"height\";s:4:\"0.80\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"variant_id\";i:11;s:12:\"variant_name\";N;s:10:\"product_id\";i:21;s:12:\"has_variants\";b:1;s:9:\"image_url\";s:61:\"/storage/uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg\";}i:2;a:28:{s:2:\"id\";i:23;s:4:\"name\";s:20:\"Smart Watch Series 5\";s:4:\"slug\";s:20:\"smart-watch-series-5\";s:11:\"description\";s:49:\"Fitness smartwatch with health tracking features.\";s:5:\"price\";s:6:\"299.00\";s:5:\"image\";s:52:\"uploads/products/1786979182_9125258a998a66d7ca96.jpg\";s:5:\"stock\";i:74;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:18;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"17.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:37:59.000000Z\";s:17:\"short_description\";s:27:\"Advanced fitness smartwatch\";s:10:\"sale_price\";s:6:\"249.00\";s:3:\"sku\";s:9:\"SKU-WT003\";s:4:\"tags\";s:19:\"watch,fitness,smart\";s:6:\"weight\";s:4:\"0.15\";s:6:\"length\";s:4:\"5.00\";s:5:\"width\";s:4:\"4.00\";s:6:\"height\";s:4:\"1.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:23;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979182_9125258a998a66d7ca96.jpg\";}i:3;a:28:{s:2:\"id\";i:30;s:4:\"name\";s:19:\"Modern Office Chair\";s:4:\"slug\";s:19:\"modern-office-chair\";s:11:\"description\";s:44:\"Ergonomic office chair with premium support.\";s:5:\"price\";s:7:\"1500.00\";s:5:\"image\";s:52:\"uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";s:5:\"stock\";i:35;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:5;s:14:\"subcategory_id\";i:13;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"3.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:42:37.000000Z\";s:17:\"short_description\";s:23:\"Comfort workspace chair\";s:10:\"sale_price\";s:7:\"1450.00\";s:3:\"sku\";s:9:\"SKU-CH010\";s:4:\"tags\";s:17:\"chair,office,home\";s:6:\"weight\";s:4:\"8.00\";s:6:\"length\";s:5:\"70.00\";s:5:\"width\";s:5:\"70.00\";s:6:\"height\";s:6:\"120.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:30;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979451_e3ba481aae7671a38c21.jpg\";}i:4;a:28:{s:2:\"id\";i:39;s:4:\"name\";s:16:\"Air Purifier Pro\";s:4:\"slug\";s:16:\"air-purifier-pro\";s:11:\"description\";s:40:\"Smart air purifier for clean indoor air.\";s:5:\"price\";s:6:\"280.00\";s:5:\"image\";s:52:\"uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";s:5:\"stock\";i:40;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:4;s:14:\"subcategory_id\";i:23;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:46:49.000000Z\";s:17:\"short_description\";s:22:\"Smart clean air system\";s:10:\"sale_price\";s:6:\"230.00\";s:3:\"sku\";s:9:\"SKU-AP019\";s:4:\"tags\";s:18:\"air,purifier,smart\";s:6:\"weight\";s:4:\"5.00\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"30.00\";s:6:\"height\";s:5:\"50.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:0;s:10:\"product_id\";i:39;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979005_3f70908be3ff8c1b2669.jpg\";}i:5;a:28:{s:2:\"id\";i:26;s:4:\"name\";s:17:\"Gaming Laptop Pro\";s:4:\"slug\";s:17:\"gaming-laptop-pro\";s:11:\"description\";s:49:\"High performance gaming laptop with powerful GPU.\";s:5:\"price\";s:8:\"25000.00\";s:5:\"image\";s:52:\"uploads/products/1786979318_7a1027c994b978f9461b.jpg\";s:5:\"stock\";i:30;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:2;s:14:\"subcategory_id\";i:35;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"12.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:52.000000Z\";s:17:\"short_description\";s:26:\"Professional gaming laptop\";s:10:\"sale_price\";s:8:\"22000.00\";s:3:\"sku\";s:9:\"SKU-LP006\";s:4:\"tags\";s:16:\"laptop,gaming,pc\";s:6:\"weight\";s:4:\"2.20\";s:6:\"length\";s:5:\"36.00\";s:5:\"width\";s:5:\"25.00\";s:6:\"height\";s:4:\"2.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:26;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979318_7a1027c994b978f9461b.jpg\";}i:6;a:28:{s:2:\"id\";i:22;s:4:\"name\";s:23:\"Wireless Headphones Pro\";s:4:\"slug\";s:23:\"wireless-headphones-pro\";s:11:\"description\";s:52:\"Noise cancelling wireless headphones with deep bass.\";s:5:\"price\";s:6:\"450.00\";s:5:\"image\";s:52:\"uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";s:5:\"stock\";i:100;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:1;s:14:\"subcategory_id\";i:10;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:4:\"4.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:47:42.000000Z\";s:17:\"short_description\";s:22:\"Premium ANC headphones\";s:10:\"sale_price\";s:6:\"430.00\";s:3:\"sku\";s:9:\"SKU-AU002\";s:4:\"tags\";s:24:\"audio,headphone,wireless\";s:6:\"weight\";s:4:\"0.30\";s:6:\"length\";s:5:\"18.00\";s:5:\"width\";s:5:\"16.00\";s:6:\"height\";s:4:\"8.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:22;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979119_827a7552f6a33cf707c9.jpg\";}i:7;a:28:{s:2:\"id\";i:25;s:4:\"name\";s:19:\"Running Shoes Ultra\";s:4:\"slug\";s:19:\"running-shoes-ultra\";s:11:\"description\";s:50:\"Lightweight running shoes with comfort technology.\";s:5:\"price\";s:6:\"120.00\";s:5:\"image\";s:52:\"uploads/products/1786979284_797514453ee3b47e9447.jpg\";s:5:\"stock\";i:120;s:6:\"status\";s:6:\"active\";s:11:\"category_id\";i:3;s:14:\"subcategory_id\";i:19;s:6:\"rating\";s:4:\"4.00\";s:8:\"discount\";s:5:\"18.00\";s:10:\"created_at\";s:27:\"2026-08-04T21:35:16.000000Z\";s:10:\"updated_at\";s:27:\"2026-09-25T09:39:06.000000Z\";s:17:\"short_description\";s:20:\"Sports running shoes\";s:10:\"sale_price\";s:5:\"99.00\";s:3:\"sku\";s:9:\"SKU-SH005\";s:4:\"tags\";s:19:\"shoes,sport,running\";s:6:\"weight\";s:4:\"0.60\";s:6:\"length\";s:5:\"30.00\";s:5:\"width\";s:5:\"15.00\";s:6:\"height\";s:5:\"10.00\";s:11:\"is_featured\";b:1;s:7:\"is_home\";b:1;s:10:\"product_id\";i:25;s:10:\"variant_id\";i:0;s:12:\"has_variants\";b:0;s:9:\"image_url\";s:61:\"/storage/uploads/products/1786979284_797514453ee3b47e9447.jpg\";}}',1790573496);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `status` (`status`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_cache`
--

DROP TABLE IF EXISTS `ci4_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_cache`
--

LOCK TABLES `ci4_cache` WRITE;
/*!40000 ALTER TABLE `ci4_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci4_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_cache_locks`
--

DROP TABLE IF EXISTS `ci4_cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_cache_locks`
--

LOCK TABLES `ci4_cache_locks` WRITE;
/*!40000 ALTER TABLE `ci4_cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci4_cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_failed_jobs`
--

DROP TABLE IF EXISTS `ci4_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_failed_jobs`
--

LOCK TABLES `ci4_failed_jobs` WRITE;
/*!40000 ALTER TABLE `ci4_failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci4_failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_job_batches`
--

DROP TABLE IF EXISTS `ci4_job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_job_batches`
--

LOCK TABLES `ci4_job_batches` WRITE;
/*!40000 ALTER TABLE `ci4_job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci4_job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_jobs`
--

DROP TABLE IF EXISTS `ci4_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_jobs`
--

LOCK TABLES `ci4_jobs` WRITE;
/*!40000 ALTER TABLE `ci4_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci4_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci4_migrations`
--

DROP TABLE IF EXISTS `ci4_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci4_migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci4_migrations`
--

LOCK TABLES `ci4_migrations` WRITE;
/*!40000 ALTER TABLE `ci4_migrations` DISABLE KEYS */;
INSERT INTO `ci4_migrations` VALUES (1,'2026-07-31-000001','App\\Database\\Migrations\\CreateUsersTable','default','App',1785502148,1),(2,'2026-07-31-000002','App\\Database\\Migrations\\CreateProductsTable','default','App',1785502148,1),(3,'2026-07-31-000003','App\\Database\\Migrations\\CreateOrdersTable','default','App',1785502148,1),(4,'2026-07-31-000004','App\\Database\\Migrations\\CreateOrderItemsTable','default','App',1785502148,1),(5,'2026-07-31-000005','App\\Database\\Migrations\\CreateSettingsTable','default','App',1785502148,1),(6,'2026-07-31-000006','App\\Database\\Migrations\\CreateFaqsTable','default','App',1785502148,1),(7,'2026-07-31-000007','App\\Database\\Migrations\\CreateContactMessagesTable','default','App',1785502148,1),(8,'2026-07-31-000009','App\\Database\\Migrations\\CreateWishlistTable','default','App',1785504518,2),(9,'2026-07-31-000010','App\\Database\\Migrations\\UpdateContactMessagesTable','default','App',1785508872,3),(10,'2026-07-31-000011','App\\Database\\Migrations\\CreateInvoicesTable','default','App',1785509060,4),(11,'2026-07-31-000012','App\\Database\\Migrations\\CreateBlogTable','default','App',1785509549,5),(12,'2026-07-31-000013','App\\Database\\Migrations\\UpdateFaqsTable','default','App',1785509549,5),(13,'2026-07-31-000014','App\\Database\\Migrations\\AddAvatarToUsers','default','App',1785509677,6),(14,'2026-07-31-000015','App\\Database\\Migrations\\UpdateUsersTable','default','App',1785509677,6),(15,'2026-07-31-000016','App\\Database\\Migrations\\UpdateProductsTable','default','App',1785510104,7),(16,'2026-07-31-000017','App\\Database\\Migrations\\UpdateUsersAddressFields','default','App',1785510811,8),(17,'2024_01_01_000000','App\\Database\\Migrations\\CreatePreferencesTables','default','App',1785582112,9),(18,'2024_01_01_000000','App\\Database\\Migrations\\AddIsHomeToProducts','default','App',1785859436,10),(19,'2026-08-06-000002','App\\Database\\Migrations\\CreateCategoriesTable','default','App',1786004466,11),(20,'2026-08-06-000002','App\\Database\\Migrations\\AddFeatureManagementTables','default','App',1786004510,12),(21,'2026_08_06_000006','App\\Database\\Migrations\\CreateFeatureValuesTable','default','App',1786011476,13),(22,'2026_08_06_000007','App\\Database\\Migrations\\CreateProductVariantsTable','default','App',1786024741,14),(23,'2026_08_06_000008','App\\Database\\Migrations\\CreateProductVariantValuesTable','default','App',1786024741,14),(24,'2026_08_06_000009','App\\Database\\Migrations\\CreateProductVariantImagesTable','default','App',1786028075,15),(25,'2026_08_10_000001','App\\Database\\Migrations\\CreateNavigationMenus','default','App',1786349585,16),(26,'2026_08_11_000000','App\\Database\\Migrations\\AddProductSpecificationsTable','default','App',1786466318,17),(27,'2026_08_17_000000','App\\Database\\Migrations\\AddRazorpayFieldsToOrders','default','App',1786951687,18);
/*!40000 ALTER TABLE `ci4_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('unread','read','replied') COLLATE utf8mb4_general_ci DEFAULT 'unread',
  `admin_reply` text COLLATE utf8mb4_general_ci,
  `replied_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'test test','1@tester.ibmhub.nl',NULL,'Subject: terw\n\nwewqeqwewe','read',NULL,NULL,'2026-09-18 12:19:51','2026-08-08 16:28:03');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `order` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'What payment methods are available?','We accept online payments through supported payment methods\navailable at checkout.','Payment','active',1,'2026-08-01 12:49:35','2026-09-25 09:01:17'),(2,'How can I track my order?','You can track your order from the Orders section in your account.\nOpen the order to view its current status and delivery information.','Orders','active',0,'2026-08-08 16:11:16','2026-09-25 09:00:43'),(3,'How long does delivery take?','Delivery time depends on your location and the product.\nEstimated delivery information will be shown during checkout.','Delivery','active',2,'2026-09-18 12:34:49','2026-09-25 09:01:48'),(4,'Can I return a product?','Yes, eligible products can be returned according to our\nreturn policy. Please check the product and order return\nconditions before requesting a return.','Returns','active',3,'2026-09-25 09:02:22','2026-09-25 09:02:34'),(5,'Do I need an account to place an order?','Yes, you need to log in or create an account to complete\nyour order and manage your order information.','Account','active',4,'2026-09-25 09:03:02','2026-09-25 09:03:02');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_category_mapping`
--

DROP TABLE IF EXISTS `feature_category_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_category_mapping` (
  `id` int NOT NULL AUTO_INCREMENT,
  `feature_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `sub_category_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feature_id` (`feature_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_sub_category_id` (`sub_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_category_mapping`
--

LOCK TABLES `feature_category_mapping` WRITE;
/*!40000 ALTER TABLE `feature_category_mapping` DISABLE KEYS */;
INSERT INTO `feature_category_mapping` VALUES (31,6,1,9,'2026-08-08 12:52:49'),(32,6,1,10,'2026-08-08 12:52:49'),(33,6,1,12,'2026-08-08 12:52:49'),(34,6,6,NULL,'2026-08-08 12:52:49'),(35,1,1,9,'2026-09-25 10:22:58'),(36,1,1,10,'2026-09-25 10:22:58'),(37,1,1,12,'2026-09-25 10:22:58'),(38,1,1,32,'2026-09-25 10:22:58'),(39,1,1,33,'2026-09-25 10:22:58'),(40,1,2,17,'2026-09-25 10:22:58'),(41,1,2,34,'2026-09-25 10:22:58'),(42,1,2,35,'2026-09-25 10:22:58'),(43,1,3,18,'2026-09-25 10:22:58'),(44,1,3,19,'2026-09-25 10:22:58'),(45,1,3,20,'2026-09-25 10:22:58'),(46,1,3,21,'2026-09-25 10:22:58'),(47,1,4,22,'2026-09-25 10:22:58'),(48,1,4,23,'2026-09-25 10:22:58'),(49,1,5,13,'2026-09-25 10:22:58'),(50,1,5,14,'2026-09-25 10:22:58'),(51,1,28,36,'2026-09-25 10:22:58'),(52,1,29,37,'2026-09-25 10:22:58'),(53,1,30,38,'2026-09-25 10:22:58'),(54,1,31,39,'2026-09-25 10:22:58');
/*!40000 ALTER TABLE `feature_category_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_values`
--

DROP TABLE IF EXISTS `feature_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `feature_id` int unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feature_values_feature_id_foreign` (`feature_id`),
  CONSTRAINT `feature_values_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_values`
--

LOCK TABLES `feature_values` WRITE;
/*!40000 ALTER TABLE `feature_values` DISABLE KEYS */;
INSERT INTO `feature_values` VALUES (1,1,'#4b52b9',0,1,'2026-08-06 10:18:39','2026-08-06 10:18:39'),(2,6,'64',0,1,'2026-08-06 12:32:33','2026-08-06 12:32:33'),(3,6,'128',0,1,'2026-08-06 12:32:41','2026-08-06 12:32:41'),(4,1,'#3700ff',0,1,'2026-08-07 12:46:07','2026-08-07 12:46:07'),(5,1,'#4d8613',0,1,'2026-08-07 16:21:33','2026-08-07 16:21:33');
/*!40000 ALTER TABLE `feature_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `features`
--

DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `input_type` enum('text','dropdown','checkbox') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `options` text COLLATE utf8mb4_general_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `features`
--

LOCK TABLES `features` WRITE;
/*!40000 ALTER TABLE `features` DISABLE KEYS */;
INSERT INTO `features` VALUES (1,'color','checkbox',NULL,1,'2026-08-06 09:32:10','2026-08-06 09:32:10'),(2,'brand','checkbox','apple,samsung',1,'2026-08-06 09:35:23','2026-08-06 12:36:45'),(3,'storage','checkbox','',1,'2026-08-06 09:35:31','2026-08-06 09:35:31'),(4,'ram','checkbox','',1,'2026-08-06 09:36:30','2026-08-06 09:36:39'),(5,'camera','checkbox','',1,'2026-08-06 09:37:30','2026-08-06 09:37:30'),(6,'battery','checkbox','',1,'2026-08-06 09:38:11','2026-08-06 12:36:08');
/*!40000 ALTER TABLE `features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_banner`
--

DROP TABLE IF EXISTS `home_banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `home_banner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `badge_text` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_line1` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_line2` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subtitle` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `button_text` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `button_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `button_icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_banner`
--

LOCK TABLES `home_banner` WRITE;
/*!40000 ALTER TABLE `home_banner` DISABLE KEYS */;
INSERT INTO `home_banner` VALUES (1,'Premium Collection','Shop with','Style & Elegance','Discover our curated collection of premium products','Explore All Products','#products','bi-arrow-right',1,'2026-08-05 09:43:51','2026-08-05 09:43:51');
/*!40000 ALTER TABLE `home_banner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('paid','unpaid','overdue','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid',
  `notes` text COLLATE utf8mb4_general_ci,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `invoices_order_id_foreign` (`order_id`),
  KEY `invoices_user_id_foreign` (`user_id`),
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `native_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `flag` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'en','English','English','🇬🇧',1,1,NULL,NULL),(2,'es','Spanish','Español','🇪🇸',1,0,NULL,NULL),(3,'fr','French','Français','🇫🇷',1,0,NULL,NULL),(4,'de','German','Deutsch','🇩🇪',1,0,NULL,NULL),(5,'it','Italian','Italiano','🇮🇹',1,0,NULL,NULL),(6,'pt','Portuguese','Português','🇵🇹',1,0,NULL,NULL),(7,'ru','Russian','Русский','🇷🇺',1,0,NULL,NULL),(8,'zh','Chinese','中文','🇨🇳',1,0,NULL,NULL),(9,'ja','Japanese','日本語','🇯🇵',1,0,NULL,NULL),(10,'ar','Arabic','العربية','🇸🇦',1,0,NULL,NULL);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_09_05_051213_create_personal_access_tokens_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `navigation_menus`
--

DROP TABLE IF EXISTS `navigation_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `navigation_menus` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `display_header` tinyint(1) NOT NULL DEFAULT '0',
  `display_footer` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `display_header` (`display_header`),
  KEY `display_footer` (`display_footer`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `navigation_menus`
--

LOCK TABLES `navigation_menus` WRITE;
/*!40000 ALTER TABLE `navigation_menus` DISABLE KEYS */;
INSERT INTO `navigation_menus` VALUES (1,'Home','/',1,1,1,1,'2026-08-10 08:19:41','2026-08-10 08:19:41'),(2,'Blog','/blog',1,1,3,1,'2026-08-10 08:20:08','2026-08-10 08:22:44'),(3,'FAQ','/faq',1,1,4,1,'2026-08-10 08:21:20','2026-08-10 08:22:50'),(4,'Product','/category',1,1,2,1,'2026-08-10 08:22:37','2026-08-10 08:22:37'),(5,'Contact','/contact',1,1,5,1,'2026-08-10 08:23:06','2026-08-10 08:23:06');
/*!40000 ALTER TABLE `navigation_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `variant_id` int DEFAULT '0',
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `idx_variant_id` (`variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (3,12,21,11,1,80.00,80.00,0.00,8.00,NULL),(4,12,23,0,1,249.00,249.00,0.00,24.90,NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `razorpay_order_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `razorpay_payment_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `razorpay_signature` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_method_display` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded','partially_refunded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `shipping_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `idx_razorpay_order_id` (`razorpay_order_id`),
  KEY `idx_razorpay_payment_id` (`razorpay_payment_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (12,1,'ORD-20260818-2131CD','order_TR93scto8VY2tN','pay_TR95I1DO581y5g','3ec1996a59bc35ae5fde417de65267222ae75c17cfe23a7ec89dae567402dcbe','razorpay',361.90,'paid','','{\"address\":\"Oldenzaalsestraat 6-u\",\"city\":\"Denekamp\",\"postal_code\":\"7591GM\",\"phone\":\"1234567890\"}','2026-08-18 07:09:54','2026-08-18 07:11:59'),(13,1,'ORD-20260923-218F61','order_TfUqBGZFKzK10D',NULL,NULL,'razorpay',339.90,'pending','cancelled','\"{\\\"address\\\":\\\"Oldenzaalsestraat 6-u\\\",\\\"city\\\":\\\"Denekamp\\\",\\\"postal_code\\\":\\\"7591GM\\\",\\\"phone\\\":\\\"9080021539\\\"}\"','2026-09-23 13:34:26','2026-09-25 08:29:58'),(14,1,'ORD-20260923-1A5293','order_TfUvF4cS81LA9d',NULL,NULL,'razorpay',339.90,'pending','pending','\"{\\\"address\\\":\\\"Oldenzaalsestraat 6-u\\\",\\\"city\\\":\\\"Denekamp\\\",\\\"postal_code\\\":\\\"7591GM\\\",\\\"phone\\\":\\\"9080021539\\\"}\"','2026-09-23 13:39:13','2026-09-23 13:39:14'),(15,1,'ORD-20260926-13A78A','order_TgZjku70aO923G',NULL,NULL,'razorpay',1683.00,'pending','pending','\"{\\\"address\\\":\\\"201,Madathupatti Street\\\",\\\"city\\\":\\\"Srivilliputhur\\\",\\\"postal_code\\\":\\\"626125\\\",\\\"phone\\\":\\\"9080021539\\\"}\"','2026-09-26 07:00:49','2026-09-26 07:00:49');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (11,'App\\Models\\User',4,'auth-token','1c6457c0700548f04ba7c0e921e4dbb1b0c52d05a28aead6df6f32180f51135e','[\"*\"]',NULL,NULL,'2026-09-20 04:20:26','2026-09-20 04:20:26'),(14,'App\\Models\\User',1,'admin-token','7d5cc3d88334b2222fb277c7fd6be70e24025cdaba58bee465df64a3c04843ea','[\"admin\"]','2026-09-25 06:33:00',NULL,'2026-09-20 05:06:25','2026-09-25 06:33:00'),(15,'App\\Models\\User',1,'admin-token','52e082d679510bfa769bac7475047744dfaadc5d174a2c208cc1889659ae6969','[\"admin\"]',NULL,NULL,'2026-09-25 01:51:50','2026-09-25 01:51:50'),(16,'App\\Models\\User',1,'admin-token','f869ed10327c3942a1b1f58f65149145a31fca45b1d6a0157a01c26daa3a1836','[\"admin\"]','2026-09-26 01:31:15',NULL,'2026-09-25 04:27:26','2026-09-26 01:31:15'),(17,'App\\Models\\User',1,'admin-token','8f4ae853de0419071fdce7e0bb5a508809fe698efe361c2ee8dad01a2a60b46c','[\"admin\"]','2026-09-28 05:26:50',NULL,'2026-09-26 09:08:52','2026-09-28 05:26:50');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'bi-box',
  `item_count` int DEFAULT '0',
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '#',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,NULL,'Electronics','bi-laptop',245,'/category/electronics',1,1,NULL,NULL),(2,NULL,'Computers & Accessories','bi-bag',189,'/category/accessories',2,1,NULL,'2026-09-25 09:16:56'),(3,NULL,'Fashion','bi-house',134,'/category/fashion',3,1,NULL,'2026-09-25 09:16:34'),(4,NULL,'Home Appliances','bi-flower1',156,'/category/homeappliances',4,1,NULL,'2026-09-25 09:17:23'),(5,NULL,'Furniture','bi-bicycle',87,'/category/furniture',5,1,NULL,'2026-09-25 09:17:44'),(9,1,'Mobile Phones','bi-box',0,'#',0,1,'2026-08-06 09:01:24','2026-09-25 09:21:34'),(10,1,'Headphones','bi-box',0,'#',0,1,'2026-08-06 09:01:35','2026-09-25 09:21:43'),(12,1,'Televisions','bi-box',0,'#',0,1,'2026-08-06 09:02:08','2026-09-25 09:21:53'),(13,5,'Chairs','bi-box',0,'#',0,1,'2026-08-06 09:04:22','2026-09-25 09:24:35'),(14,5,'Tables','bi-box',0,'#',0,1,'2026-08-06 09:04:37','2026-09-25 09:24:43'),(17,2,'Keyboards','bi-box',0,'#',0,1,'2026-08-06 09:05:12','2026-09-25 09:22:24'),(18,3,'Watches','bi-box',0,'#',0,1,'2026-08-06 09:05:48','2026-09-25 09:23:10'),(19,3,'Shoes','bi-box',0,'#',0,1,'2026-08-06 09:05:57','2026-09-25 09:23:20'),(20,3,'Backpacks','bi-box',0,'#',0,1,'2026-08-06 09:06:02','2026-09-25 09:23:39'),(21,3,'Sunglasses','bi-box',0,'#',0,1,'2026-08-06 09:06:08','2026-09-25 09:23:29'),(22,4,'Coffee Machines','bi-box',0,'#',0,1,'2026-08-06 09:09:40','2026-09-25 09:24:04'),(23,4,'Air Purifiers','bi-box',0,'#',0,1,'2026-08-06 09:09:46','2026-09-25 09:24:13'),(27,6,'trewrwr','rwrwer',0,'#',4,1,'2026-09-19 06:11:35','2026-09-19 06:11:35'),(28,NULL,'Travel','bi-box',0,'/category/travel',6,1,'2026-09-25 09:18:08','2026-09-25 09:26:08'),(29,NULL,'Gaming','bi-box',0,'/category/gaming',7,1,'2026-09-25 09:19:17','2026-09-25 09:19:17'),(30,NULL,'Wearables','bi-box',0,'/category/wearables',8,1,'2026-09-25 09:19:42','2026-09-25 09:19:42'),(31,NULL,'Mobile Accessories','bi-box',0,'/category/mobileAccessories',9,1,'2026-09-25 09:20:13','2026-09-25 09:20:13'),(32,1,'Cameras','bi-box',0,'#',0,1,'2026-09-25 09:22:00','2026-09-25 09:22:00'),(33,1,'Speakers','bi-box',0,'#',0,1,'2026-09-25 09:22:08','2026-09-25 09:22:08'),(34,2,'Mice','bi-box',0,'#',0,1,'2026-09-25 09:22:35','2026-09-25 09:22:35'),(35,2,'Laptops','bi-box',0,'#',0,1,'2026-09-25 09:22:45','2026-09-25 09:22:45'),(36,28,'Travel Bags','bi-box',0,'#',0,1,'2026-09-25 09:25:05','2026-09-25 09:25:05'),(37,29,'Gaming Controllers','bi-box',0,'#',0,1,'2026-09-25 09:25:22','2026-09-25 09:25:22'),(38,30,'Fitness Trackers','bi-box',0,'#',0,1,'2026-09-25 09:25:38','2026-09-25 09:25:38'),(39,31,'Power Banks','bi-box',0,'#',0,1,'2026-09-25 09:25:55','2026-09-25 09:25:55');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_feature_values`
--

DROP TABLE IF EXISTS `product_feature_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_feature_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `feature_id` int unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_feature_values_feature_id_foreign` (`feature_id`),
  KEY `idx_product_feature` (`product_id`,`feature_id`),
  CONSTRAINT `product_feature_values_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_feature_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_feature_values`
--

LOCK TABLES `product_feature_values` WRITE;
/*!40000 ALTER TABLE `product_feature_values` DISABLE KEYS */;
INSERT INTO `product_feature_values` VALUES (49,22,6,'3','2026-08-08 09:46:46','2026-08-08 09:46:46'),(50,22,6,'2','2026-08-08 09:46:46','2026-08-08 09:46:46'),(51,22,1,'4','2026-08-08 09:46:46','2026-08-08 09:46:46'),(52,22,1,'5','2026-08-08 09:46:46','2026-08-08 09:46:46'),(89,21,1,'4',NULL,NULL),(90,21,1,'1',NULL,NULL),(91,21,1,'1',NULL,NULL),(92,21,1,'4',NULL,NULL),(93,21,1,'5',NULL,NULL),(94,21,6,'3',NULL,NULL),(95,21,6,'2',NULL,NULL),(96,21,6,'2',NULL,NULL),(97,21,6,'3',NULL,NULL);
/*!40000 ALTER TABLE `product_feature_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,21,'uploads/products/1785932136_29e8b74a13b96d5372cf.jpg',0,0,'2026-08-05 12:15:36','2026-08-05 12:20:06'),(2,21,'uploads/products/1785932136_e480ca58364fd5eb9ff1.jpg',1,1,'2026-08-05 12:15:36','2026-08-05 12:20:06'),(3,21,'uploads/products/1785935604_cedd554d2a844ac290be.jpg',0,2,'2026-08-05 13:13:24','2026-08-05 13:13:24'),(12,40,'uploads/products/1786978750_cb31f50e319bbf67fa2d.jpg',1,0,'2026-08-17 14:59:10','2026-09-25 09:26:46');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_specifications`
--

DROP TABLE IF EXISTS `product_specifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_specifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_specifications_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_specifications`
--

LOCK TABLES `product_specifications` WRITE;
/*!40000 ALTER TABLE `product_specifications` DISABLE KEYS */;
INSERT INTO `product_specifications` VALUES (1,22,'Battery','5000mAh',0,NULL,'2026-08-11 16:44:13'),(2,22,'Display','6.7Amlod',1,NULL,'2026-08-11 16:44:13'),(3,22,'Storage','128GB',2,'2026-08-11 16:44:13','2026-08-11 16:44:13'),(4,21,'Battery','5000 Mah',0,NULL,NULL),(5,21,'Display','6.3 inch',1,NULL,NULL);
/*!40000 ALTER TABLE `product_specifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_images`
--

DROP TABLE IF EXISTS `product_variant_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variant_images_variant_id_foreign` (`variant_id`),
  KEY `product_variant_images_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variant_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_variant_images_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_images`
--

LOCK TABLES `product_variant_images` WRITE;
/*!40000 ALTER TABLE `product_variant_images` DISABLE KEYS */;
INSERT INTO `product_variant_images` VALUES (23,11,21,'uploads/variants/1787032595_23f9682a2f45a076dc3a.jpg',1,0,'2026-08-18 05:56:35','2026-08-18 05:56:35'),(24,12,21,'uploads/variants/1787032642_f577a8c34b930e247a0d.jpg',1,0,'2026-08-18 05:57:22','2026-08-18 05:57:22'),(25,13,21,'uploads/variants/1787032666_49f455acb202fec330af.jpg',1,0,'2026-08-18 05:57:46','2026-08-18 05:57:46'),(26,14,21,'uploads/variants/1787032691_d30e13cb832e482c7102.jpg',1,0,'2026-08-18 05:58:11','2026-08-18 05:58:11');
/*!40000 ALTER TABLE `product_variant_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_values`
--

DROP TABLE IF EXISTS `product_variant_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `feature_id` int unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variant_values_variant_id_foreign` (`variant_id`),
  KEY `product_variant_values_feature_id_foreign` (`feature_id`),
  CONSTRAINT `product_variant_values_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_variant_values_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_values`
--

LOCK TABLES `product_variant_values` WRITE;
/*!40000 ALTER TABLE `product_variant_values` DISABLE KEYS */;
INSERT INTO `product_variant_values` VALUES (15,11,1,'4','2026-08-10 15:53:11','2026-08-10 15:53:11'),(16,11,6,'3','2026-08-10 15:53:11','2026-08-10 15:53:11'),(17,12,1,'1','2026-08-10 15:53:12','2026-08-10 15:53:12'),(18,12,6,'3','2026-08-10 15:53:12','2026-08-10 15:53:12'),(19,13,1,'4','2026-08-10 15:53:12','2026-08-10 15:53:12'),(20,13,6,'2','2026-08-10 15:53:12','2026-08-10 15:53:12'),(21,14,1,'1','2026-08-10 15:53:12','2026-08-10 15:53:12'),(22,14,6,'2','2026-08-10 15:53:12','2026-08-10 15:53:12');
/*!40000 ALTER TABLE `product_variant_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(10,2) DEFAULT NULL,
  `discount` decimal(5,2) DEFAULT '0.00',
  `rating` decimal(2,1) DEFAULT '0.0',
  `stock` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (11,21,'p1','premium-smartphone-x1-3-4-p1',100.00,80.00,20.00,4.0,10,1,'2026-08-10 15:53:11','2026-09-19 06:06:23'),(12,21,'p2','premium-smartphone-x1-3-1-p2',110.00,85.00,23.00,4.0,10,1,'2026-08-10 15:53:12','2026-08-10 15:53:12'),(13,21,'p3','premium-smartphone-x1-2-4-p3',150.00,140.00,7.00,3.0,8,1,'2026-08-10 15:53:12','2026-08-10 16:35:29'),(14,21,'p4','premium-smartphone-x1-2-1-p4',150.00,145.00,3.00,4.0,16,1,'2026-08-10 15:53:12','2026-08-10 15:53:12');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `category_id` int DEFAULT NULL,
  `subcategory_id` int DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT '0.0',
  `discount` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `short_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_home` tinyint(1) DEFAULT '0' COMMENT 'Show product on homepage',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  KEY `fk_products_category` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `idx_products_subcategory_id` (`subcategory_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (21,'Premium Smartphone X1','premium-smartphone-x1','Latest flagship smartphone with powerful performance and premium camera.',799.00,'uploads/products/1786979800_009800244e7e0449382d.jpg',25,'active',1,9,4.0,10,'2026-08-04 21:35:16','2026-08-17 15:16:40','High performance 5G smartphone',699.00,'SKU-PH001','phone,5g,mobile',0.20,15.00,7.00,0.80,1,1),(22,'Wireless Headphones Pro','wireless-headphones-pro','Noise cancelling wireless headphones with deep bass.',450.00,'uploads/products/1786979119_827a7552f6a33cf707c9.jpg',100,'active',1,10,4.0,4,'2026-08-04 21:35:16','2026-09-25 09:47:42','Premium ANC headphones',430.00,'SKU-AU002','audio,headphone,wireless',0.30,18.00,16.00,8.00,1,1),(23,'Smart Watch Series 5','smart-watch-series-5','Fitness smartwatch with health tracking features.',299.00,'uploads/products/1786979182_9125258a998a66d7ca96.jpg',74,'active',3,18,4.0,17,'2026-08-04 21:35:16','2026-09-25 09:37:59','Advanced fitness smartwatch',249.00,'SKU-WT003','watch,fitness,smart',0.15,5.00,4.00,1.00,1,1),(24,'Luxury Leather Bag','luxury-leather-bag','Premium leather handbag for modern lifestyle.',1500.00,'uploads/products/1786979249_64a2a8872a76d1de19ec.jpg',40,'active',3,20,4.0,3,'2026-08-04 21:35:16','2026-09-25 09:49:10','Elegant leather fashion bag',1450.00,'SKU-BG004','bag,leather,fashion',0.80,35.00,25.00,10.00,1,1),(25,'Running Shoes Ultra','running-shoes-ultra','Lightweight running shoes with comfort technology.',120.00,'uploads/products/1786979284_797514453ee3b47e9447.jpg',120,'active',3,19,4.0,18,'2026-08-04 21:35:16','2026-09-25 09:39:06','Sports running shoes',99.00,'SKU-SH005','shoes,sport,running',0.60,30.00,15.00,10.00,1,1),(26,'Gaming Laptop Pro','gaming-laptop-pro','High performance gaming laptop with powerful GPU.',25000.00,'uploads/products/1786979318_7a1027c994b978f9461b.jpg',30,'active',2,35,4.0,12,'2026-08-04 21:35:16','2026-09-25 09:39:52','Professional gaming laptop',22000.00,'SKU-LP006','laptop,gaming,pc',2.20,36.00,25.00,2.00,1,1),(27,'Mechanical Keyboard RGB','mechanical-keyboard-rgb','RGB mechanical keyboard for gamers.',1000.00,'uploads/products/1786979352_506b42a3c1a9bdea5ba8.jpg',80,'active',2,17,4.0,5,'2026-08-04 21:35:16','2026-09-25 09:40:41','RGB gaming keyboard',950.00,'SKU-KB007','keyboard,gaming,rgb',0.90,45.00,15.00,5.00,0,1),(28,'Wireless Mouse','wireless-mouse','Ergonomic wireless mouse with fast response.',250.00,'uploads/products/1786979391_04bb43e34683288caf04.jpg',150,'active',2,34,4.0,10,'2026-08-04 21:35:16','2026-09-25 09:41:27','Comfort wireless mouse',225.00,'SKU-MS008','mouse,wireless,computer',0.10,12.00,7.00,4.00,0,1),(29,'Coffee Machine Premium','coffee-machine-premium','Automatic coffee maker for home and office.',15000.00,'uploads/products/1786979421_bfda7c631047caf1a3ae.jpg',25,'active',4,22,4.0,4,'2026-08-04 21:35:16','2026-09-25 09:42:07','Automatic espresso machine',14450.00,'SKU-CF009','coffee,kitchen,machine',4.50,40.00,25.00,30.00,1,0),(30,'Modern Office Chair','modern-office-chair','Ergonomic office chair with premium support.',1500.00,'uploads/products/1786979451_e3ba481aae7671a38c21.jpg',35,'active',5,13,4.0,3,'2026-08-04 21:35:16','2026-09-25 09:42:37','Comfort workspace chair',1450.00,'SKU-CH010','chair,office,home',8.00,70.00,70.00,120.00,1,0),(31,'Smart LED TV 55 Inch','smart-led-tv-55','4K smart television with streaming support.',45000.00,'uploads/products/1786979484_5f8548d7abe9ac742333.jpg',20,'active',1,12,4.0,7,'2026-08-04 21:35:16','2026-09-25 09:43:05','4K smart LED television',42000.00,'SKU-TV011','tv,smart,4k',12.00,125.00,8.00,75.00,1,1),(32,'Digital Camera Pro','digital-camera-pro','Professional camera for photography lovers.',110000.00,'uploads/products/1786979526_44a3751eb5ac9776f084.jpg',15,'active',1,32,4.0,10,'2026-08-04 21:35:16','2026-09-25 09:49:56','High resolution digital camera',99000.00,'SKU-CM012','camera,photo,dslr',1.20,15.00,8.00,12.00,1,0),(33,'Travel Backpack','travel-backpack','Water resistant backpack for travel.',1000.00,'uploads/products/1786979583_7fd61371c58a9d374b63.jpg',90,'active',28,36,4.0,5,'2026-08-04 21:35:16','2026-09-25 09:43:52','Premium travel backpack',950.00,'SKU-BP013','bag,travel,backpack',0.70,45.00,30.00,15.00,0,0),(34,'Smart Home Speaker','smart-home-speaker','Voice assistant smart speaker.',500.00,'uploads/products/1786979620_8063c5003e003cfd6daa.jpg',60,'active',1,33,4.0,10,'2026-08-04 21:35:16','2026-09-25 09:44:10','Smart voice speaker',450.00,'SKU-SP014','speaker,smart,home',1.00,15.00,15.00,20.00,0,1),(35,'Gaming Controller','gaming-controller','Wireless gaming controller compatible with PC.',300.00,'uploads/products/1786979655_62ee6b07d02eb38648cd.jpg',70,'active',29,37,0.0,17,'2026-08-04 21:35:16','2026-09-25 09:44:29','Wireless game controller',250.00,'SKU-GC015','game,controller,wireless',0.40,16.00,10.00,6.00,0,0),(36,'Fitness Tracker Band','fitness-tracker-band','Track steps, heart rate and sleep.',1150.00,'uploads/products/1786979709_8cb0eb6143c3f64ed90c.jpg',110,'active',30,38,0.0,4,'2026-08-04 21:35:16','2026-09-25 09:44:46','Health tracking band',1100.00,'SKU-FB016','fitness,band,health',0.05,5.00,3.00,1.00,0,0),(37,'Wooden Dining Table','wooden-dining-table','Modern wooden dining table design.',25000.00,'uploads/products/1786979740_043b1e472090147a1a28.jpg',10,'active',5,14,5.0,4,'2026-08-04 21:35:16','2026-09-25 09:45:21','Premium dining furniture',24000.00,'SKU-TB017','table,wood,home',25.00,180.00,90.00,75.00,1,0),(38,'Premium Sunglasses','premium-sunglasses','Stylish sunglasses with UV protection.',150.00,'uploads/products/1786979770_3fea42e7ab633822905a.jpg',100,'active',3,21,4.0,13,'2026-08-04 21:35:16','2026-09-25 09:45:54','Fashion sunglasses',130.00,'SKU-SG018','glasses,fashion,uv',0.05,15.00,5.00,5.00,0,0),(39,'Air Purifier Pro','air-purifier-pro','Smart air purifier for clean indoor air.',280.00,'uploads/products/1786979005_3f70908be3ff8c1b2669.jpg',40,'active',4,23,4.0,18,'2026-08-04 21:35:16','2026-09-25 09:46:49','Smart clean air system',230.00,'SKU-AP019','air,purifier,smart',5.00,30.00,30.00,50.00,1,0),(40,'Portable Power Bank','portable-power-bank','Fast charging power bank with large capacity.',1500.00,'uploads/products/1786978890_d33dbe4ab9667f83e09a.jpg',200,'active',31,39,4.0,7,'2026-08-04 21:35:16','2026-09-25 09:50:39','Fast charging battery pack',1400.00,'SKU-PB020','battery,charger,power',0.25,15.00,7.00,2.00,0,1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','BSSShop','2026-07-31 15:14:39','2026-09-23 06:49:23'),(2,'site_description','Your one-stop shop for everything','2026-07-31 15:14:39','2026-09-23 06:49:23'),(3,'site_email','sivaram1712000@gmail.com','2026-07-31 15:14:39','2026-09-23 06:49:23'),(4,'site_phone','+91 9080021539','2026-07-31 15:14:39','2026-09-23 06:49:23'),(5,'site_address','Madathupatti Street,Srivilliputhur,Viruthunagar -625125','2026-07-31 15:14:39','2026-09-23 06:49:23'),(6,'store_currency','INR','2026-07-31 15:14:39','2026-09-23 06:49:23'),(7,'store_tax','10','2026-07-31 15:14:39','2026-09-23 06:49:23'),(8,'store_shipping','6','2026-07-31 15:14:39','2026-09-23 06:49:23'),(9,'store_free_shipping','50','2026-07-31 15:14:39','2026-09-23 06:49:23'),(10,'payment_stripe_key','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(11,'payment_stripe_secret','PhhoneFurb123!','2026-07-31 15:14:39','2026-09-23 06:49:23'),(12,'payment_ideal_enabled','0','2026-07-31 15:14:39','2026-09-23 06:49:23'),(13,'payment_stripe_enabled','0','2026-07-31 15:14:39','2026-09-23 06:49:23'),(14,'social_facebook','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(15,'social_twitter','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(16,'social_instagram','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(17,'social_youtube','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(18,'social_linkedin','https://linkedin.com/in/sankarkutty','2026-07-31 15:14:39','2026-09-23 06:49:23'),(19,'email_protocol','mail','2026-07-31 15:14:39','2026-09-23 06:49:23'),(20,'email_smtp_host','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(21,'email_smtp_port','587','2026-07-31 15:14:39','2026-09-23 06:49:23'),(22,'email_smtp_user','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(23,'email_smtp_pass','','2026-07-31 15:14:39','2026-09-23 06:49:23'),(24,'seo_meta_title','BSSShop - Online Store','2026-07-31 15:14:39','2026-09-23 06:49:23'),(25,'seo_meta_description','Shop the best products at BSSShop','2026-07-31 15:14:39','2026-09-23 06:49:23'),(26,'seo_meta_keywords','shop, ecommerce, products','2026-07-31 15:14:39','2026-09-23 06:49:23'),(27,'user_preferences_1','{\"notifications\":\"on\",\"newsletter\":\"0\",\"language\":\"en\",\"timezone\":\"UTC\",\"theme\":\"light\"}','2026-07-31 15:16:48','2026-09-18 11:56:12'),(28,'favicon','settings/favicon.ico',NULL,'2026-09-18 11:55:09'),(29,'payment_razorpay_key','rzp_test_TQUVHY6PotwJ9m','2026-08-17 06:52:33','2026-09-23 06:49:23'),(30,'payment_razorpay_secret','1sTmNdvG4wCRD9FXwMclUXKe','2026-08-17 06:52:33','2026-09-23 06:49:23'),(31,'payment_razorpay_enabled','1','2026-08-17 06:56:18','2026-09-23 06:49:23'),(32,'order_notifications','1','2026-09-16 07:23:27','2026-09-23 06:49:23'),(33,'newsletter_enabled','1','2026-09-16 07:23:28','2026-09-23 06:49:23'),(34,'tax_rate','10','2026-09-18 09:44:36','2026-09-18 11:56:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `preview_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `themes`
--

LOCK TABLES `themes` WRITE;
/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES (1,'light','Light','Light color theme',NULL,1,1,NULL,NULL),(2,'dark','Dark','Dark color theme',NULL,1,0,NULL,NULL),(3,'auto','Auto','Auto detect system theme',NULL,0,0,NULL,'2026-08-01 12:09:17');
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timezones`
--

DROP TABLE IF EXISTS `timezones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timezones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `offset` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `abbreviation` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timezones`
--

LOCK TABLES `timezones` WRITE;
/*!40000 ALTER TABLE `timezones` DISABLE KEYS */;
INSERT INTO `timezones` VALUES (1,'UTC','+00:00','UTC',1,1,NULL,NULL),(2,'America/New_York','-05:00','EST',1,0,NULL,NULL),(3,'America/Chicago','-06:00','CST',1,0,NULL,NULL),(4,'America/Denver','-07:00','MST',1,0,NULL,NULL),(5,'America/Los_Angeles','-08:00','PST',1,0,NULL,NULL),(6,'Europe/London','+00:00','GMT',1,0,NULL,NULL),(7,'Europe/Paris','+01:00','CET',1,0,NULL,NULL),(8,'Asia/Dubai','+04:00','GST',1,0,NULL,NULL),(9,'Asia/Tokyo','+09:00','JST',1,0,NULL,NULL),(10,'Australia/Sydney','+11:00','AEDT',1,0,NULL,NULL);
/*!40000 ALTER TABLE `timezones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `theme` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `notifications` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
  `newsletter` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
INSERT INTO `user_preferences` VALUES (1,1,'en','UTC','light','on',1,'2026-09-12 06:44:08','2026-09-19 01:35:12'),(2,4,'en','UTC','light','on',0,'2026-09-20 04:20:26','2026-09-20 04:20:26'),(3,5,'en','UTC','light','on',0,'2026-09-20 04:35:02','2026-09-20 04:35:02');
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'US',
  `notes` text COLLATE utf8mb4_general_ci,
  `super_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@bssshop.com','$2y$12$BvkFr87yPgD7p/eLaSdame9LOztw3uZWCuHd3MKfE6srcfb7itesq','admin','active','2026-07-31 13:01:55','2026-09-26 09:54:02','9080021539','avatars/YiTUsR6PcX1jwa3ON1NvSMDPLTyoAVxGO3zMXR9T.jpg','201,Madathupatti Street','Srivilliputhur','Tamil Nadu','626125','India',NULL,1),(2,'test test','1@tester.ibmhub.nl','$2y$10$AivcXqoZtoov0vO1ssQ7.uZvCDbg.csImNmo.l/YmTEqRW9GolUP2','user','active','2026-07-31 16:49:28','2026-08-01 16:05:17','1234567890',NULL,NULL,NULL,NULL,NULL,'US',NULL,0),(3,'testsep','testsep@gmail.com','$2y$12$R5F.8zYerABerNJUYh0yMugs1TRNNKqjbyWE.JgPpcukXaMNcrtiO','user','active','2026-09-19 06:38:30','2026-09-19 06:38:30','9080021539',NULL,'srivilliputhur','virudhunagar','tamilnadu','626125','IN',NULL,0),(5,'sankarkutty','sankarkutty@gmail.com','$2y$12$kMp/us.0whnNb6wjpybCkegNDquaiVlY8zXKV8jAc6Gqstyr036vO','user','active','2026-09-20 10:05:02','2026-09-20 10:05:02',NULL,NULL,NULL,NULL,NULL,NULL,'US',NULL,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `variant_id` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_product_id` (`user_id`,`product_id`),
  KEY `wishlist_product_id_foreign` (`product_id`),
  CONSTRAINT `wishlist_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wishlist_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (37,1,22,0,'2026-09-23 13:46:28'),(38,1,21,11,'2026-09-23 14:03:00'),(39,1,30,0,'2026-09-25 07:22:34');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-28  7:14:48
