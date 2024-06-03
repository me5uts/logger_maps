<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use PDO;
use PDOException;
use uLogger\Helper\Utils;

/**
 * PDO wrapper
 */
class Db extends PDO {
  /**
   * Singleton instance
   *
   * @var Db Object instance
   */
  protected static $instance;

  /**
   * Table names
   *
   * @var array Array of names
   */
  protected static $tables;

  /**
   * Database driver name
   *
   * @var string Driver
   */
  protected static $driver;

  /**
   * @var string Database DSN
   */
  private static $dbdsn = "";
  /**
   * @var string Database user
   */
  private static $dbuser = "";
  /**
   * @var string Database pass
   */
  private static $dbpass = "";
  /**
   * @var string Optional table names prefix, eg. "ulogger_"
   */
  private static $dbprefix = "";

    /**
   * PDO constuctor
   *
   * @param string $dsn
   * @param string $user
   * @param string $pass
   */
  public function __construct(string $dsn, string $user, string $pass) {
    try {
      $options = [
        PDO::ATTR_EMULATE_PREPARES   => false, // try to use native prepared statements
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // return assoc array by default
      ];
      @parent::__construct($dsn, $user, $pass, $options);
      self::$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
      $this->setCharset("utf8");
      $this->initTables();
    } catch (PDOException $e) {
      header("HTTP/1.1 503 Service Unavailable");
      die("Database connection error (" . $e->getMessage() . ")");
    }
  }

  /**
   * Initialize table names based on config
   */
  private function initTables(): void {
    self::$tables = [];
    $prefix = preg_replace('/[^a-z0-9_]/i', '', self::$dbprefix);
    self::$tables['positions'] = $prefix . "positions";
    self::$tables['tracks'] = $prefix . "tracks";
    self::$tables['users'] = $prefix . "users";
    self::$tables['config'] = $prefix . "config";
    self::$tables['ol_layers'] = $prefix . "ol_layers";
  }

  /**
   * Returns singleton instance
   *
   * @return Db Singleton instance
   */
  public static function getInstance(): Db {
    if (!self::$instance) {
      self::getConfig();
      self::$instance = new self(self::$dbdsn, self::$dbuser, self::$dbpass);
    }
    return self::$instance;
  }

  /**
   * Read database setup from config file
   * @noinspection IssetArgumentExistenceInspection
   */
  private static function getConfig(): void {
    $configFile = dirname(__DIR__, 2) . "/config.php";
    if (!file_exists($configFile)) {
      header("HTTP/1.1 503 Service Unavailable");
      die("Missing config.php file!");
    }
    include($configFile);
    if (isset($dbdsn)) {
      self::$dbdsn = self::normalizeDsn($dbdsn);
    }
    if (isset($dbuser)) {
      self::$dbuser = $dbuser;
    }
    if (isset($dbpass)) {
      self::$dbpass = $dbpass;
    }
    if (isset($dbprefix)) {
      self::$dbprefix = $dbprefix;
    }
  }

  /**
   * Get full table name including prefix
   *
   * @param string $name Name
   * @return string Full table name
   */
  public function table(string $name): string {
    return self::$tables[$name];
  }

  /**
   * Returns function name for getting date-time column value as unix timestamp
   * @param string $column
   * @return string
   */
  public function unix_timestamp(string $column): string {
    switch (self::$driver) {
      default:
      case "mysql":
        return "UNIX_TIMESTAMP($column)";
      case "pgsql":
        return "EXTRACT(EPOCH FROM $column::TIMESTAMP WITH TIME ZONE)";
      case "sqlite":
        return "STRFTIME('%s', $column)";
    }
  }

  /**
   * Returns placeholder for LOB data types
   * @return string
   */
  public function lobPlaceholder(): string {
    switch (self::$driver) {
      default:
      case "mysql":
      case "sqlite":
        return "?";
      case "pgsql":
        return "?::bytea";
    }
  }

  /**
   * Returns construct for getting LOB as string
   * @param string $column Column name
   * @return string
   */
  public function from_lob(string $column): string {
    switch (self::$driver) {
      default:
      case "mysql":
      case "sqlite":
        return $column;
      case "pgsql":
        return "encode($column, 'escape') AS $column";
    }
  }

  /**
   * Returns function name for getting date-time column value as 'YYYY-MM-DD hh:mm:ss'
   * @param string $column
   * @return string
   */
  public function from_unixtime(string $column): string {
    switch (self::$driver) {
      default:
      case "mysql":
        return "FROM_UNIXTIME($column)";
      case "pgsql":
        return "TO_TIMESTAMP($column)";
      case "sqlite":
        return "DATETIME($column, 'unixepoch')";
    }
  }

  /**
   * Replace into
   * Note: requires PostgreSQL >= 9.5
   * @param string $table Table name (without prefix)
   * @param string[] $columns Column names
   * @param string[][] $values Values [ [ value1, value2 ], ... ]
   * @param string $key Unique column
   * @param string $update Updated column
   * @return string
   */
  public function insertOrReplace(string $table, array $columns, array $values, string $key, string $update): string {
    $cols = implode(", ", $columns);
    $rows = [];
    foreach ($values as $row) {
      $rows[] = "(" . implode(", ", $row) . ")";
    }
    $vals = implode(", ", $rows);
    switch (self::$driver) {
      default:
      case "mysql":
        return "INSERT INTO {$this->table($table)} ($cols)
                VALUES $vals
                ON DUPLICATE KEY UPDATE $update = VALUES($update)";
      case "pgsql":
        return "INSERT INTO {$this->table($table)} ($cols)
                VALUES $vals
                ON CONFLICT ($key) DO UPDATE SET $update = EXCLUDED.$update";
      case "sqlite":
        return "REPLACE INTO {$this->table($table)} ($cols)
                VALUES $vals";
    }
  }

  /**
   * Set character set
   * @param string $charset
   * @noinspection PhpSameParameterValueInspection
   */
  private function setCharset(string $charset): void {
    if (self::$driver === "pgsql" || self::$driver === "mysql") {
      $this->exec("SET NAMES '$charset'");
    }
  }

  /**
   * Extract database name from DSN
   * @param string $dsn
   * @return string Empty string if not found
   */
  public static function getDbName(string $dsn): string {
    $name = "";
    if (strpos($dsn, ":") !== false) {
      [$scheme, $dsnWithoutScheme] = explode(":", $dsn, 2);
      switch ($scheme) {
        case "sqlite":
        case "sqlite2":
        case "sqlite3":
          $pattern = "/(.+)/";
          break;
        case "pgsql":
          $pattern = "/dbname=([^; ]+)/";
          break;
        default:
          $pattern = "/dbname=([^;]+)/";
          break;
      }
      $result = preg_match($pattern, $dsnWithoutScheme, $matches);
      if ($result === 1) {
        $name = $matches[1];
      }
    }
    return $name;
  }

  /**
   * Normalize DSN.
   * Make sure sqlite DSN file path is absolute
   * @param string $dsn DSN
   * @return string Normalized DSN
   */
  public static function normalizeDsn(string $dsn): string {
    if (stripos($dsn, "sqlite") !== 0) {
      return $dsn;
    }
    $arr = explode(":", $dsn, 2);
    if (count($arr) < 2 || empty($arr[1]) || Utils::isAbsolutePath($arr[1])) {
      return $dsn;
    }
    $scheme = $arr[0];
    $path = Utils::getRootDir() . DIRECTORY_SEPARATOR . $arr[1];
    return $scheme . ":" . realpath(dirname($path)) . DIRECTORY_SEPARATOR . basename(($path));
  }
}
?>
