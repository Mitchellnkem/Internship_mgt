CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cus_id` int(11) NOT NULL,
  `int_id` int(11) NOT NULL,
  `applied` tinyint(1) NOT NULL DEFAULT 0,
  `application_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','reviewed','shortlisted','rejected','hired') DEFAULT 'pending',
  `cover_letter` text,
  `resume_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cus_id` (`cus_id`),
  KEY `int_id` (`int_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


