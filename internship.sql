-- BIT-InternshipHub core schema (MySQL / MariaDB, InnoDB, utf8mb4).
-- Safe to re-run for table creation. Existing tables are not dropped or truncated.

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(160) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'password_hash() output; never store plaintext',
  `user_type` varchar(32) NOT NULL DEFAULT 'student',
  `permissions` varchar(255) NOT NULL DEFAULT '' COMMENT 'Legacy comma-separated permissions; migrate to normalized RBAC before adding many roles',
  `account_status` enum('active','pending','suspended','deactivated') NOT NULL DEFAULT 'active',
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_type_status_index` (`user_type`,`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `institution` varchar(180) DEFAULT NULL,
  `program` varchar(180) DEFAULT NULL,
  `graduation_year` smallint(5) unsigned DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL COMMENT 'Private storage key, not a public filesystem path',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `student_profiles_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employer_profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(120) DEFAULT NULL,
  `company_size` varchar(40) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `employer_verification_index` (`verification_status`),
  CONSTRAINT `employer_profiles_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `internship_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internship_categories_name_unique` (`name`),
  UNIQUE KEY `internship_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `internships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employer_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `company` varchar(180) NOT NULL,
  `location` varchar(180) NOT NULL,
  `type` varchar(80) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `requirements` mediumtext DEFAULT NULL,
  `status` enum('draft','pending','published','closed','rejected') NOT NULL DEFAULT 'pending',
  `posted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closing_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `internships_public_feed_index` (`status`,`posted_at`),
  KEY `internships_location_index` (`location`),
  KEY `internships_employer_index` (`employer_id`,`status`),
  KEY `internships_category_index` (`category_id`,`status`),
  CONSTRAINT `internships_employer_fk` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internships_category_fk` FOREIGN KEY (`category_id`) REFERENCES `internship_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saved_internships` (
  `user_id` int(10) unsigned NOT NULL,
  `internship_id` int(10) unsigned NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`internship_id`),
  KEY `saved_internships_listing_index` (`internship_id`),
  KEY `saved_internships_user_date_index` (`user_id`,`saved_at`),
  CONSTRAINT `saved_internships_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_internships_listing_fk` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recently_viewed_internships` (
  `user_id` int(10) unsigned NOT NULL,
  `internship_id` int(10) unsigned NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`internship_id`),
  KEY `recently_viewed_user_date_index` (`user_id`,`viewed_at`),
  CONSTRAINT `recently_viewed_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recently_viewed_listing_fk` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cus_id` int(10) unsigned NOT NULL COMMENT 'Legacy column name; refers to users.id',
  `int_id` int(10) unsigned NOT NULL COMMENT 'Legacy column name; refers to internships.id',
  `applied` tinyint(1) NOT NULL DEFAULT 1,
  `application_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','reviewed','shortlisted','rejected','hired') NOT NULL DEFAULT 'pending',
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL COMMENT 'Private storage key, not a public filesystem path',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_student_internship_unique` (`cus_id`,`int_id`),
  KEY `applications_internship_status_index` (`int_id`,`status`),
  KEY `applications_student_date_index` (`cus_id`,`application_date`),
  CONSTRAINT `applications_student_fk` FOREIGN KEY (`cus_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `applications_internship_fk` FOREIGN KEY (`int_id`) REFERENCES `internships` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `old_status` varchar(24) DEFAULT NULL,
  `new_status` varchar(24) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_history_timeline_index` (`application_id`,`created_at`),
  KEY `application_history_actor_index` (`changed_by`,`created_at`),
  CONSTRAINT `application_history_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_history_actor_fk` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'Store a hash of a random, single-use token',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `password_reset_token_hash_unique` (`token_hash`),
  KEY `password_reset_user_expiry_index` (`user_id`,`expires_at`),
  CONSTRAINT `password_reset_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'Store a hash of a random, single-use token',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_verification_token_hash_unique` (`token_hash`),
  KEY `email_verification_user_expiry_index` (`user_id`,`expires_at`),
  CONSTRAINT `email_verification_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` text DEFAULT NULL,
  `target_url` varchar(500) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_inbox_index` (`user_id`,`read_at`,`created_at`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `details` mediumtext DEFAULT NULL COMMENT 'Non-secret audit context; never store passwords or tokens',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_audit_action_time_index` (`action`,`created_at`),
  KEY `admin_audit_entity_index` (`entity_type`,`entity_id`,`created_at`),
  KEY `admin_audit_actor_index` (`actor_user_id`,`created_at`),
  CONSTRAINT `admin_audit_actor_fk` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Short-lived response cache prevents each page view from consuming provider quota.
CREATE TABLE IF NOT EXISTS `external_opportunity_search_cache` (
  `cache_key` char(64) NOT NULL,
  `response_json` mediumtext NOT NULL,
  `fetched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`cache_key`),
  KEY `external_search_cache_expiry_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_search_api_rate_limits` (
  `client_hash` char(64) NOT NULL,
  `window_started_at` datetime NOT NULL,
  `request_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`client_hash`),
  KEY `external_rate_window_index` (`window_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store only Matrix account/room references here; Matrix owns encrypted events and device keys.
CREATE TABLE IF NOT EXISTS `matrix_user_links` (
  `user_id` int(10) unsigned NOT NULL,
  `matrix_user_id` varchar(255) NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `matrix_user_id_unique` (`matrix_user_id`),
  CONSTRAINT `matrix_user_links_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `matrix_conversations` (
  `application_id` int(10) unsigned NOT NULL,
  `matrix_room_id` varchar(255) NOT NULL,
  `created_by_user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `matrix_room_id_unique` (`matrix_room_id`),
  KEY `matrix_conversation_created_index` (`created_at`),
  CONSTRAINT `matrix_conversations_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matrix_conversations_creator_fk` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
