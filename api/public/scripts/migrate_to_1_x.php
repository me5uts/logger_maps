<?php
declare(strict_types = 1);
/**
 * μlogger
 *
 * Copyright(C) 2020 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
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
