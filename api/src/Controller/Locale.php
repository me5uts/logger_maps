<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Lang;
use uLogger\Component\Response;
use uLogger\Entity;

class Locale {
  private Entity\Config $config;

  /**
   * @param Entity\Config $config
   */
  public function __construct(Entity\Config $config) {
    $this->config = $config;
  }

  public function get(): Response {
    $langStrings = (new Lang($this->config))->getStrings();
    $result = [
      "langArr" => Lang::getLanguages()
    ];
    foreach ($langStrings as $key => $val) {
      $result[$key] = $val;
    }

    return Response::success($result);
  }
}
