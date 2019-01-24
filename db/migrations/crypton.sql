/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 80011
Source Host           : localhost:3306
Source Database       : crypton

Target Server Type    : MYSQL
Target Server Version : 80011
File Encoding         : 65001

Date: 2019-01-24 20:42:14
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for access
-- ----------------------------
DROP TABLE IF EXISTS `access`;
CREATE TABLE `access` (
  `id` int(11) NOT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `exchange_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for bots
-- ----------------------------
DROP TABLE IF EXISTS `bots`;
CREATE TABLE `bots` (
  `id` int(10) unsigned NOT NULL,
  `access_id` int(11) NOT NULL,
  `exchange_id` int(11) NOT NULL,
  `pair_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `bot` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `locked` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sellOrderPrice` double(10,10) NOT NULL,
  `buyOrderPrice` double(10,10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for bot_orders
-- ----------------------------
DROP TABLE IF EXISTS `bot_orders`;
CREATE TABLE `bot_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(10) unsigned DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `price` double(10,2) DEFAULT NULL,
  `value` double DEFAULT NULL,
  `pair_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `side` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `traded` double DEFAULT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `access_id` int(10) unsigned NOT NULL,
  `e_order_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `e_client_order_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for trades
-- ----------------------------
DROP TABLE IF EXISTS `trades`;
CREATE TABLE `trades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `access_id` int(10) unsigned DEFAULT NULL,
  `pair_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `e_order_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `e_client_order_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `side` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` double(10,8) DEFAULT NULL,
  `value` double(10,8) DEFAULT NULL,
  `fee` double(10,8) DEFAULT NULL,
  `fee_currency` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `dt` datetime DEFAULT NULL,
  `e_trade_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
