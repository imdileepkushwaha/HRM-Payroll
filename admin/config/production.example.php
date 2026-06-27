<?php
/**
 * Live server — copy to config/production.php on hosting
 * File config/production.php is gitignored (never commit passwords).
 */

define('PAYROLL_MYSQL_HOST', 'localhost');
define('PAYROLL_MYSQL_DATABASE', 'payroll_db');
define('PAYROLL_MYSQL_USER', 'payroll_db');
define('PAYROLL_MYSQL_PASS', 'YOUR_DATABASE_PASSWORD');

define('PAYROLL_ALLOW_SETUP_TOOLS', false);
