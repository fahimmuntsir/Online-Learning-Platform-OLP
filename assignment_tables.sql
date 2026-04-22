-- ============================================================
-- Assignment Feature - Run this SQL in phpMyAdmin or MySQL CLI
-- Database: learning_platform
-- ============================================================

-- Table: assignment (created by instructor)
CREATE TABLE IF NOT EXISTS `assignment` (
  `assignment_id`     INT(11) NOT NULL AUTO_INCREMENT,
  `Course_ID`         VARCHAR(20) NOT NULL,
  `instructor_id`     VARCHAR(50) NOT NULL,
  `assignment_number` INT(11) NOT NULL,
  `description`       LONGTEXT DEFAULT NULL,
  `deadline`          DATETIME NOT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `Course_ID` (`Course_ID`),
  KEY `instructor_id` (`instructor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: assignment_submission (submitted by student)
CREATE TABLE IF NOT EXISTS `assignment_submission` (
  `submission_id`  INT(11) NOT NULL AUTO_INCREMENT,
  `assignment_id`  INT(11) NOT NULL,
  `student_id`     VARCHAR(50) NOT NULL,
  `file_path`      VARCHAR(500) NOT NULL,
  `submitted_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `unique_submission` (`assignment_id`, `student_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Also create the uploads folder (do this manually on server):
-- mkdir assignment_uploads
-- chmod 755 assignment_uploads
-- ============================================================

-- ============================================================
-- UPDATE: Add question_file column to assignment table
-- Run this if assignment table already exists:
-- ============================================================
ALTER TABLE `assignment`
  ADD COLUMN IF NOT EXISTS `question_file` VARCHAR(500) DEFAULT NULL
  COMMENT 'Path to instructor-uploaded question/instructions file (PDF/DOC/DOCX)';

-- If creating fresh, the full updated table is:
-- (question_file column added)
-- CREATE TABLE IF NOT EXISTS `assignment` (
--   `assignment_id`     INT(11) NOT NULL AUTO_INCREMENT,
--   `Course_ID`         VARCHAR(20) NOT NULL,
--   `instructor_id`     VARCHAR(50) NOT NULL,
--   `assignment_number` INT(11) NOT NULL,
--   `description`       LONGTEXT DEFAULT NULL,
--   `deadline`          DATETIME NOT NULL,
--   `question_file`     VARCHAR(500) DEFAULT NULL,
--   `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`assignment_id`),
--   KEY `Course_ID` (`Course_ID`),
--   KEY `instructor_id` (`instructor_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Also create the question files upload folder (do this manually on server):
-- mkdir assignment_questions
-- chmod 755 assignment_questions
