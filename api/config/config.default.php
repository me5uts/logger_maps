<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

// This is default configuration file.
// Copy it to config.php and customize

// Database config

// PDO data source name, eg.:
// mysql:host=localhost;port=3307;dbname=ulogger;charset=utf8
// mysql:unix_socket=/path/to/mysql.sock;dbname=ulogger;charset=utf8
// pgsql:host=localhost;port=5432;dbname=ulogger
// sqlite:/path/to/ulogger.db
$dbdsn = "";

// Database username
$dbuser = "";

// Database user password
$dbpass = "";

// Optional table names prefix, eg. "ulogger_"
$dbprefix = "";

?>
