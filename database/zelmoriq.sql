-- ============================================================================
-- Zelmoriq database  |  MySQL 5.7+ / MariaDB 10.4+ (XAMPP)
--
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < database/zelmoriq.sql
-- WARNING: this file drops and recreates the Zelmoriq tables. Use it for a fresh
-- install only. It contains reference data (departments, courses, subjects) but
-- NO user accounts: create the first Manager by opening /setup.php in a browser.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `zelmoriq` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `zelmoriq`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `notes`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `teacher_profiles`;
DROP TABLE IF EXISTS `hod_profiles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `departments`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Academic structure (managed by the Manager; subjects also by each HOD)
-- ---------------------------------------------------------------------------
CREATE TABLE `departments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_department_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courses` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_id` INT UNSIGNED NOT NULL,
  `name`          VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course` (`department_id`, `name`),
  CONSTRAINT `fk_courses_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_id` INT UNSIGNED NOT NULL,
  `name`          VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subject` (`department_id`, `name`),
  CONSTRAINT `fk_subjects_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- People: ONE users table for all four roles, plus one profile table per role
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL,
  `email`            VARCHAR(150) NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `role`             ENUM('manager','hod','teacher','student') NOT NULL,
  `phone`            VARCHAR(20) NULL,
  `photo`            VARCHAR(60) NULL,
  `status`           ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `reset_token_hash` CHAR(64) NULL,
  `reset_expires`    DATETIME NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `idx_user_reset` (`reset_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hod_profiles` (
  `user_id`       INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_one_hod_per_department` (`department_id`),
  CONSTRAINT `fk_hod_user`       FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_hod_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teacher_profiles` (
  `user_id`       INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `bio`           TEXT NULL,
  `college`       VARCHAR(150) NOT NULL,
  `certificate`   VARCHAR(60) NULL,
  `approval`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by`   INT UNSIGNED NULL,
  `reviewed_at`   DATETIME NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_teacher_dept_approval` (`department_id`, `approval`),
  CONSTRAINT `fk_teacher_user`       FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_teacher_reviewer`   FOREIGN KEY (`reviewed_by`)   REFERENCES `users` (`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_profiles` (
  `user_id`   INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `semester`  TINYINT UNSIGNED NOT NULL,
  `roll_no`   VARCHAR(50) NOT NULL,
  `college`   VARCHAR(150) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_student_course` (`course_id`),
  CONSTRAINT `fk_student_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_student_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Notes and ratings
-- ---------------------------------------------------------------------------
CREATE TABLE `notes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id`    INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `subject_id`    INT UNSIGNED NOT NULL,
  `title`         VARCHAR(200) NOT NULL,
  `topic`         VARCHAR(200) NOT NULL,
  `description`   TEXT NULL,
  `semester`      TINYINT UNSIGNED NOT NULL,
  `file_name`     VARCHAR(60) NOT NULL,
  `original_name` VARCHAR(200) NOT NULL,
  `file_size`     INT UNSIGNED NOT NULL DEFAULT 0,
  `downloads`     INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notes_dept_sem` (`department_id`, `semester`),
  KEY `idx_notes_subject` (`subject_id`),
  KEY `idx_notes_teacher` (`teacher_id`),
  CONSTRAINT `fk_notes_teacher`    FOREIGN KEY (`teacher_id`)    REFERENCES `users` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_notes_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_notes_subject`    FOREIGN KEY (`subject_id`)    REFERENCES `subjects` (`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ratings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id`    INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_one_rating_per_student` (`note_id`, `student_id`),
  CONSTRAINT `chk_rating_range` CHECK (`rating` BETWEEN 1 AND 5),
  CONSTRAINT `fk_ratings_note`    FOREIGN KEY (`note_id`)    REFERENCES `notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Site plumbing
-- ---------------------------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `subject`    VARCHAR(200) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(150) NOT NULL,
  `ip`           VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempt_lookup` (`email`, `ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Reference data (edit freely later from the Manager console)
-- ---------------------------------------------------------------------------
INSERT INTO `departments` (`id`, `name`) VALUES
  (1, 'Computer Science'),
  (2, 'Information Technology'),
  (3, 'Commerce'),
  (4, 'English'),
  (5, 'Hindi'),
  (6, 'Physics'),
  (7, 'Chemistry'),
  (8, 'Mathematics');

INSERT INTO `courses` (`department_id`, `name`) VALUES
  (1, 'BCA'),
  (1, 'MCA'),
  (1, 'BSc Computer Science'),
  (2, 'BSc Information Technology'),
  (3, 'BCom'),
  (3, 'MCom'),
  (4, 'BA English'),
  (5, 'BA Hindi'),
  (6, 'BSc Physics'),
  (7, 'BSc Chemistry'),
  (8, 'BSc Mathematics');

INSERT INTO `subjects` (`department_id`, `name`) VALUES
  (1, 'Data Structures'),
  (1, 'Operating Systems'),
  (1, 'Database Management Systems'),
  (1, 'Computer Networks'),
  (1, 'Software Engineering'),
  (1, 'Web Technologies'),
  (2, 'Programming in Java'),
  (2, 'Information Security'),
  (3, 'Financial Accounting'),
  (3, 'Business Law'),
  (3, 'Corporate Accounting'),
  (4, 'British Literature'),
  (4, 'Communicative English'),
  (5, 'Hindi Sahitya'),
  (6, 'Classical Mechanics'),
  (6, 'Electromagnetism'),
  (7, 'Organic Chemistry'),
  (7, 'Physical Chemistry'),
  (8, 'Calculus'),
  (8, 'Linear Algebra');
