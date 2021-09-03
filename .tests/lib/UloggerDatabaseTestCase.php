<?php
declare(strict_types = 1);

require_once("BaseDatabaseTestCase.php");
require_once(__DIR__ . "/../../helpers/db.php");

class UloggerDatabaseTestCase extends BaseDatabaseTestCase {

  /**
   * @var uDb $udb
   */
  static private $udb;

  /**
   * @throws ReflectionException
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    if (file_exists(__DIR__ . '/../.env')) {
      $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
      $dotenv->load();
      $dotenv->required(['DB_DSN', 'DB_USER', 'DB_PASS']);
    }

    $db_dsn = getenv('DB_DSN');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');

    // uDb connection
    if (self::$udb == null) {
      self::$udb = new ReflectionClass('uDb');
      $dbInstance = self::$udb->getProperty('instance');
      $dbInstance->setAccessible(true);
      $dbInstance->setValue(new uDb($db_dsn, $db_user, $db_pass));
    }
  }

  public static function tearDownAfterClass(): void {
    parent::tearDownAfterClass();
    self::$udb = null;
  }
}
?>
