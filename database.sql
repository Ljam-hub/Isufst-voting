-- ==========================================================
-- Online Voting System Database Schema
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB, and InfinityFree
-- ==========================================================

-- For InfinityFree: Select your database (e.g. if0_43386545_voting) on the left sidebar, then click Import.
-- Note: CREATE DATABASE & USE are commented out below to prevent "Access Denied" errors on shared hosting.
-- CREATE DATABASE IF NOT EXISTS `online_voting_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `online_voting_db`;

-- --------------------------------------------------------
-- Table structure for `settings` (6 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `election_title` VARCHAR(255) NOT NULL DEFAULT 'University Supreme Student Council Elections',
  `academic_year` VARCHAR(50) NOT NULL DEFAULT '2026-2027',
  `organization_name` VARCHAR(255) NOT NULL DEFAULT 'ISUFST Commission on Elections (COMELEC)',
  `is_election_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Active/Open, 0 = Closed',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`id`, `election_title`, `academic_year`, `organization_name`, `is_election_active`) 
VALUES (1, 'University Supreme Student Council General Elections', '2026-2027', 'ISUFST Supreme Student Council COMELEC', 1);

-- --------------------------------------------------------
-- Table structure for `admins` (7 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Administrator',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin: username: admin | password: admin123
INSERT INTO `admins` (`id`, `username`, `password`, `fullname`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$JFjMH8p3oDChz9vbYa065.UzPdKsAjXhc4AsV/jI6igFSY5QL0frC', 'COMELEC Chief Commissioner', 'comelec@isufst.edu.ph', 'Head Commissioner');

-- --------------------------------------------------------
-- Table structure for `positions` (6 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `max_votes` INT(11) NOT NULL DEFAULT 1 COMMENT 'Number of candidates voter can pick',
  `priority` INT(11) NOT NULL DEFAULT 1 COMMENT 'Display sort order in ballot',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `positions` (`id`, `title`, `max_votes`, `priority`, `description`) VALUES
(1, 'President', 1, 1, 'Chief Executive of the Supreme Student Council'),
(2, 'Vice President', 1, 2, 'Assists the President and oversees council committees'),
(3, 'Secretary General', 1, 3, 'Manages official records, council communications, and minutes'),
(4, 'Treasurer', 1, 4, 'Oversees council finances, budget allocation, and fund disbursement'),
(5, 'Auditor', 1, 5, 'Audits and certifies financial transparency of the student body'),
(6, 'Public Information Officer (P.I.O.)', 1, 6, 'Handles press releases, social media, and student announcements'),
(7, 'College Representative', 2, 7, 'Represents student affairs (Vote up to 2 candidates)');

-- --------------------------------------------------------
-- Table structure for `candidates` (8 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `position_id` INT(11) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `party` VARCHAR(100) NOT NULL DEFAULT 'Independent',
  `platform` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT 'default_candidate.svg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_candidates_position` (`position_id`),
  CONSTRAINT `fk_candidates_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `candidates` (`id`, `position_id`, `first_name`, `last_name`, `party`, `platform`, `photo`) VALUES
(1, 1, 'Mark', 'Villanueva', 'Sulong ISUFST Alliance', 'Advocating for 24/7 library access, free campus Wi-Fi expansion, and transparent student financial aid.', 'default_candidate.svg'),
(2, 1, 'Sophia', 'Reyes', 'Tindig Kabataan Party', 'Empowering student leaders through research grants, student welfare protection, and mental health hubs.', 'default_candidate.svg'),
(3, 2, 'Christian', 'Dela Cruz', 'Sulong ISUFST Alliance', 'Strengthening inter-department sports programs, club equipment subsidies, and leadership conferences.', 'default_candidate.svg'),
(4, 2, 'Camille', 'Santos', 'Tindig Kabataan Party', 'Digital student services portal, streamlined grievances handling, and eco-friendly green campus projects.', 'default_candidate.svg'),
(5, 3, 'Jasmine', 'Gonzales', 'Sulong ISUFST Alliance', 'Fast online document requests, automated event registration, and transparent student bulletin boards.', 'default_candidate.svg'),
(6, 4, 'Joshua', 'Ramirez', 'Tindig Kabataan Party', 'Quarterly published financial balance sheets and cashless student organization payment systems.', 'default_candidate.svg'),
(7, 5, 'Patricia', 'Navarro', 'Sulong ISUFST Alliance', 'Zero-leakage student fund audits and bi-monthly public audit reports on student council expenditure.', 'default_candidate.svg'),
(8, 6, 'Angelo', 'Mercado', 'Tindig Kabataan Party', 'Real-time multi-platform student notification channels and campus news podcast production.', 'default_candidate.svg'),
(9, 7, 'Bea', 'Aquino', 'Sulong ISUFST Alliance', 'Representing Information Technology and Engineering students with modern laboratory equipment lobbying.', 'default_candidate.svg'),
(10, 7, 'Daniel', 'Salazar', 'Tindig Kabataan Party', 'Representing Education and Arts students for expanded internship partnerships and research stipends.', 'default_candidate.svg'),
(11, 7, 'Erika', 'Perez', 'Independent', 'Independent voice championing commuter safety, student lounge facilities, and canteen price monitoring.', 'default_candidate.svg');

-- --------------------------------------------------------
-- Table structure for `voters` (10 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `voters`;
CREATE TABLE `voters` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `voter_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Student Identification Number',
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `course` VARCHAR(50) NOT NULL DEFAULT 'BSIT',
  `year_level` VARCHAR(20) NOT NULL DEFAULT '1st Year',
  `has_voted` TINYINT(1) NOT NULL DEFAULT 0,
  `voted_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Pre-registered Voters (password is 'voter123')
INSERT INTO `voters` (`id`, `voter_id`, `first_name`, `last_name`, `email`, `password`, `course`, `year_level`, `has_voted`, `voted_at`) VALUES
(1, '2026-00101', 'Juan', 'Dela Cruz', 'juan.delacruz@isufst.edu.ph', '$2y$10$2OVr1TSNTodPYiRLIM/D2OQkTM.NGmyZgjBBNym2GMyWuMBwcvaqu', 'BSIT', '3rd Year', 0, NULL),
(2, '2026-00102', 'Maria', 'Santos', 'maria.santos@isufst.edu.ph', '$2y$10$2OVr1TSNTodPYiRLIM/D2OQkTM.NGmyZgjBBNym2GMyWuMBwcvaqu', 'BSCS', '2nd Year', 0, NULL),
(3, '2026-00103', 'Carlos', 'Garcia', 'carlos.garcia@isufst.edu.ph', '$2y$10$2OVr1TSNTodPYiRLIM/D2OQkTM.NGmyZgjBBNym2GMyWuMBwcvaqu', 'BSIS', '4th Year', 0, NULL),
(4, '2026-00104', 'Alyssa', 'Mendoza', 'alyssa.mendoza@isufst.edu.ph', '$2y$10$2OVr1TSNTodPYiRLIM/D2OQkTM.NGmyZgjBBNym2GMyWuMBwcvaqu', 'BSED', '1st Year', 0, NULL),
(5, '2026-00105', 'Kevin', 'Tan', 'kevin.tan@isufst.edu.ph', '$2y$10$2OVr1TSNTodPYiRLIM/D2OQkTM.NGmyZgjBBNym2GMyWuMBwcvaqu', 'BSIT', '2nd Year', 0, NULL);

-- --------------------------------------------------------
-- Table structure for `votes` (6 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `voter_id` INT(11) NOT NULL,
  `position_id` INT(11) NOT NULL,
  `candidate_id` INT(11) NOT NULL,
  `ballot_receipt` VARCHAR(64) NOT NULL,
  `vote_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_votes_voter` (`voter_id`),
  KEY `fk_votes_position` (`position_id`),
  KEY `fk_votes_candidate` (`candidate_id`),
  CONSTRAINT `fk_votes_voter` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_votes_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_votes_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `activity_logs` (6 fields)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_type` VARCHAR(20) NOT NULL COMMENT 'Admin or Voter',
  `user_identifier` VARCHAR(100) NOT NULL COMMENT 'username or student id',
  `action` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `activity_logs` (`user_type`, `user_identifier`, `action`, `ip_address`) VALUES
('Admin', 'admin', 'Database initialized with CRUD engine and election schema', '127.0.0.1');
