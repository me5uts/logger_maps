<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Exception\ServerException;
use uLogger\Mapper\MapperFactory;

abstract class AbstractController {

  public function __construct(
    protected MapperFactory $mapperFactory,
    protected Session $session,
    protected Entity\Config $config
  ) {
  }

  /**
   * @template T
   * @param class-string<T> $className
   * @return T
   * @throws ServerException
   */
  protected function mapper(string $className): mixed {
    return $this->mapperFactory->getMapper($className);
  }
}
