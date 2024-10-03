<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper;

use stdClass;
use uLogger\Exception\ServerException;
use uLogger\Mapper\User;

class MapperFactoryTest extends AbstractMapperTestCase {

  /**
   * @throws ServerException
   */
  public function testFactorySuccess(): void {

    $class = User::class;
    $mapper = $this->mapperFactory->getMapper($class);

    $this->assertInstanceOf($class, $mapper);
  }

  /**
   * @throws ServerException
   */
  public function testFactoryCacheSuccess(): void {

    $class = User::class;
    $mapper = $this->mapperFactory->getMapper($class);
    $mapper2 = $this->mapperFactory->getMapper($class);

    $this->assertInstanceOf($class, $mapper);
    $this->assertSame($mapper, $mapper2);
  }

  public function testFactoryNotExistingClass(): void {

    $class = 'NotExistingClass';

    $this->expectException(ServerException::class);

    $this->mapperFactory->getMapper($class);
  }

  public function testFactoryNotMapperClass(): void {

    $class = Stdclass::class;

    $this->expectException(ServerException::class);

    $this->mapperFactory->getMapper($class);
  }

}
