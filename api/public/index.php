<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Router;
use uLogger\Entity\Config;
use uLogger\Middleware;

$config = Config::getInstance();
$auth = new Auth();

$accessControl = new Middleware\AccessControl($auth);
$request = new Request();
$request->loadFromServer();

$router = new Router();
$router->addMiddleware($accessControl);
$router->setupRoutes($auth, $config);

try {
  $response = $router->dispatch($request);
} catch (Exception $e) {
  $response = Response::internalServerError($e->getMessage());
}

$response->sendAndExit();

?>
