-- MySQL database for HRM / Payroll portal
-- Import in phpMyAdmin or: mysql -u root < admin/scripts/init_hrm_db_mysql.sql
-- Then run admin/setup.php OR scripts/migrate_mssql_to_mysql.php to copy MSSQL data.

CREATE DATABASE IF NOT EXISTS `hrm_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hrm_db`;

-- Tables are created/updated automatically by admin/setup.php via ensure_database_schema().
-- This file only creates the empty database. For a full fresh install:
--   1. Import this file (creates hrm_db)
--   2. Open http://localhost/.../admin/setup.php
-- To copy existing MSSQL payroll_db data:
--   php admin/scripts/migrate_mssql_to_mysql.php
