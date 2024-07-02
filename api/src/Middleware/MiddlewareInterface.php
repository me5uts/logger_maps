<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Middleware;

use uLogger\Attribute\Route;
use uLogger\Component\Request;
use uLogger\Component\Response;

interface MiddlewareInterface {
  public function run(Request $request, Route $route): Response;
}
