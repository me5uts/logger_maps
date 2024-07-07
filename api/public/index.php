<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../vendor/autoload.php');

use uLogger\Component\Db;
use uLogger\Component\ErrorHandler;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Router;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Mapper\MapperFactory;
use uLogger\Middleware;

try {
  ErrorHandler::init();

  $mapperFactory = new MapperFactory(Db::createFromConfig());
  $config = Entity\Config::createFromMapper($mapperFactory);
  $session = new Session($mapperFactory, $config);
  $session->init();

  $accessControl = new Middleware\AccessControl($mapperFactory, $session);
  $request = new Request();
  $request->loadFromServer();

  $router = new Router();
  $router->addMiddleware($accessControl);

  $router->setupRoutes($session, $config, $mapperFactory);
  $response = $router->dispatch($request);
} catch (Exception $e) {
  $response = Response::exception($e);
}

$response->sendAndExit();

?>
