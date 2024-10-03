<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper;

use PDO;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;
use uLogger\Component\Db;
use uLogger\Exception\DatabaseException;
use uLogger\Mapper\MapperFactory;

class AbstractMapperTestCase extends TestCase {

  use DatabaseTestTrait;

  private PDO $pdo;
  protected Db $db;
  protected MapperFactory $mapperFactory;

  /**
   * @throws DatabaseException
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpConnection($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    $this->setUpDatabase(__DIR__ . '/Schemas/mysql.sql');
  }

  public function getConnection(): PDO {
    return $this->pdo;
  }

  /**
   * @param class-string $className
   * @param string $key
   * @param mixed $value
   * @return array|null
   */
  public function getRecordByKey(string $className, string $key, mixed $value): array|null {
    $array = (new $className())->records;
    return $this->getArrayRowByKey($array, $key, $value);
  }

  /**
   * @param class-string $className
   * @param int $id
   * @return array|null
   */
  public function getRecordById(string $className, int $id): array|null {
    return $this->getRecordByKey($className, 'id', $id);
  }

  /**
   * @param string $dsn
   * @param string $user
   * @param string $password
   * @return void
   * @throws DatabaseException
   */
  private function setUpConnection(string $dsn, string $user, string $password): void {
    $this->pdo = new PDO($dsn, $user, $password);
    $this->db = new Db($dsn, $user, $password);
    $this->mapperFactory = new MapperFactory($this->db);
  }

  /**
   * Fetch row by ID.
   *
   * @param string $table Table name
   * @return array Row
   */
  protected function getTableAllRows(string $table): array {
    $sql = sprintf('SELECT * FROM `%s`', $table);
    $statement = $this->createPreparedStatement($sql);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * @param mixed $array
   * @param string $key
   * @param mixed $value
   * @return mixed|null
   */
  public function getArrayRowByKey(array $array, string $key, mixed $value): array|null {
    foreach ($array as $record) {
      if ($record[$key] === $value) {
        return $record;
      }
    }
    return null;
  }

}
