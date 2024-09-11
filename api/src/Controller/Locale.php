<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Attribute\Route;
use uLogger\Component\Lang;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Session;

class Locale extends AbstractController {

  /**
   * GET /locales (list of languages, translated strings for current language; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/locales', [ Session::ACCESS_ALL => [ Session::ALLOW_ALL ] ])]
  public function get(): Response {
    $langStrings = (new Lang($this->config))->getStrings();
    $result = [
      'langArr' => Lang::getLanguages()
    ];
    foreach ($langStrings as $key => $val) {
      $result[$key] = $val;
    }

    return Response::success($result);
  }
}
