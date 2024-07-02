<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Session;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class Config {

  private Entity\Config $config;
  /** @var Mapper\Config */
  private Mapper\Config $mapper;

  /**
   * @param MapperFactory $mapperFactory
   * @param Entity\Config $config
   */
  public function __construct(Mapper\MapperFactory $mapperFactory, Entity\Config $config) {
    $this->config = $config;
    $this->mapper = $mapperFactory->getMapper(Mapper\Config::class);
  }

  /**
   * Get config
   * GET /config (get configuration; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/config', [ Session::ACCESS_ALL => [ Session::ALLOW_ALL ] ])]
  public function get(): Response {

    return Response::success($this->config);
  }

  /**
   * PUT /config (save configuration; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param Entity\Config $config
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/config', [ Session::ACCESS_ALL => [ Session::ALLOW_ADMIN ] ])]
  public function update(Entity\Config $config): Response {

    $config->setUploadLimit();
    try {
      if ($this->mapper->update($config) === false) {
        return Response::internalServerError("servererror");
      }
      $this->config->setFromConfig($config);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }
    return Response::success();
  }
}
