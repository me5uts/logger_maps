<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

/*
 * CLI script for database migration from μlogger version 0.6 to version 1.x.
 * Database user defined in config file must have privileges to create and modify tables.
 *
 * Backup your database before running this script.
 */

require_once('../../vendor/autoload.php');

use uLogger\Helper\Migration;

if (PHP_SAPI !== "cli") {
  die("This script must be called from CLI console");
}

if (!defined("SKIP_RUN")) {
  Migration::verifyVersion() || exit(1);
  Migration::waitForUser() || exit(0);
  Migration::updateSchemas() || exit(1);
  Migration::updateConfig() || exit(1);
  exit(0);
}

?>
