<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use Exception;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\GpxParseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
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

  /**
   * @param Exception $e
   * @return Response
   */
  protected function exceptionResponse(Exception $e): Response {
    if ($e instanceof DatabaseException) {
      return Response::databaseError($e->getMessage());
    } elseif ($e instanceof ServerException) {
      return Response::internalServerError($e->getMessage());
    } elseif ($e instanceof InvalidInputException || $e instanceof GpxParseException) {
      return Response::unprocessableError($e->getMessage());
    } elseif ($e instanceof NotFoundException) {
      return Response::notFound();
    }
    return Response::internalServerError("An unexpected error occurred.");
  }
}
