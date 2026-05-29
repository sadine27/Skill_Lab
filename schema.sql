-- Smart Waste Aggregation System — Database Schema
-- Target: MySQL / MariaDB (XAMPP)
--
-- Setup (XAMPP):
--   1. Start Apache + MySQL in the XAMPP control panel
--   2. Open http://localhost/phpmyadmin
--   3. Import this file (it creates the database and all tables), OR run:
--        mysql -u root < schema.sql
--
-- Re-importing is safe: it drops and recreates the schema from scratch.

DROP DATABASE IF EXISTS waste_system;
CREATE DATABASE waste_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE waste_system;

-- ---------------------------------------------------------------------------
-- users: citizens, collectors, and admins all live here, split by `role`
-- ---------------------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('citizen','collector','admin') NOT NULL DEFAULT 'citizen',
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- waste_categories: lookup table populated by the seed data below
-- ---------------------------------------------------------------------------
CREATE TABLE waste_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- waste_reports: the core entity citizens create and collectors/admins act on
-- ---------------------------------------------------------------------------
CREATE TABLE waste_reports (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  citizen_id    INT NOT NULL,
  category_id   INT NOT NULL,
  description   TEXT NOT NULL,
  location_text VARCHAR(255) NOT NULL,
  photo_path    VARCHAR(255) DEFAULT NULL,
  status        ENUM('pending','in_progress','collected','rejected') NOT NULL DEFAULT 'pending',
  assigned_to   INT DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_report_citizen  FOREIGN KEY (citizen_id)  REFERENCES users(id)            ON DELETE CASCADE,
  CONSTRAINT fk_report_category FOREIGN KEY (category_id) REFERENCES waste_categories(id),
  CONSTRAINT fk_report_assignee FOREIGN KEY (assigned_to) REFERENCES users(id)            ON DELETE SET NULL,
  INDEX idx_reports_status   (status),
  INDEX idx_reports_citizen  (citizen_id),
  INDEX idx_reports_assignee (assigned_to)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- collection_logs: audit trail of actions a collector takes on a report
-- ---------------------------------------------------------------------------
CREATE TABLE collection_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  report_id    INT NOT NULL,
  collector_id INT NOT NULL,
  action       VARCHAR(60) NOT NULL,
  notes        VARCHAR(255) DEFAULT NULL,
  logged_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_report    FOREIGN KEY (report_id)    REFERENCES waste_reports(id) ON DELETE CASCADE,
  CONSTRAINT fk_log_collector FOREIGN KEY (collector_id) REFERENCES users(id)         ON DELETE CASCADE,
  INDEX idx_logs_report (report_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

-- Waste categories (referenced by the citizen submit form)
INSERT INTO waste_categories (name, description) VALUES
  ('Household',     'General domestic / kitchen waste'),
  ('Recyclable',    'Paper, plastic, glass, and metal'),
  ('Organic',       'Garden, food, and compostable waste'),
  ('Electronic',    'E-waste: batteries, devices, cables'),
  ('Construction',  'Debris, rubble, and demolition waste'),
  ('Hazardous',     'Chemicals, medical, or toxic materials');

-- Default admin account so you can log in immediately after import.
-- Email:    admin@waste.local
-- Password: admin123
-- (Change this password after first login. Hash generated with password_hash().)
INSERT INTO users (name, email, password_hash, role) VALUES
  ('System Admin', 'admin@waste.local',
   '$2y$12$JdBUHJpHSJ4TxvDECrne9u03.gI3ak9jAW8J8pGYm/Q/i2J3RHPD.', 'admin');
