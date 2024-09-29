<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use Exception;
use uLogger\Attribute\Route;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Mapper;

class Config extends AbstractController {

  /**
   * Get config
   * GET /config (get configuration; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/config', [ Session::ACCESS_ALL => [ Session::ALLOW_ALL ] ])]
  public function get(): Response {

    return Response::success($this->config);
  }

  /**
   * PUT /config (save configuration; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param Entity\Config $config
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_PUT, '/api/config', [ Session::ACCESS_ALL => [ Session::ALLOW_ADMIN ] ])]
  public function update(Entity\Config $config): Response {

    $config->setUploadLimit();
    if (!$config->requireAuthentication) {
      // tracks must be public if we don't require authentication
      $config->publicTracks = true;
    }
    try {
      $this->mapper(Mapper\Config::class)->update($config);
      $this->config->setFromConfig($config);
    } catch (Exception $e) {
      return Response::exception($e);
    }
    return Response::success();
  }
}
