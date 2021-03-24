CREATE TABLE IF NOT EXISTS `%table-name%` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `parent_queue_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `force_retry` tinyint(1) unsigned DEFAULT NULL,
  `reserved_at_gmt` int(10) unsigned DEFAULT NULL,
  `completed_at_gmt` int(10) unsigned DEFAULT NULL,
  `created_at_gmt` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=1527 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;