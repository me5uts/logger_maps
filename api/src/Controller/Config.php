<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Response;
use uLogger\Entity;
use uLogger\Entity\Layer;

class Config {
  private Entity\Config $config;

  /**
   * @param Entity\Config $config
   */
  public function __construct(Entity\Config $config) {
    $this->config = $config;
  }

  public function get(): Response {
    $resultConfig = [
      "colorExtra" => $this->config->colorExtra,
      "colorHilite" => $this->config->colorHilite,
      "colorNormal" => $this->config->colorNormal,
      "colorStart" => $this->config->colorStart,
      "colorStop" => $this->config->colorStop,
      "googleKey" => $this->config->googleKey,
      "initLatitude" => $this->config->initLatitude,
      "initLongitude" => $this->config->initLongitude,
      "interval" => $this->config->interval,
      "lang" => $this->config->lang,
      "mapApi" => $this->config->mapApi,
      "passLenMin" => $this->config->passLenMin,
      "passStrength" => $this->config->passStrength,
      "publicTracks" => $this->config->publicTracks,
      "requireAuth" => $this->config->requireAuthentication,
      "strokeColor" => $this->config->strokeColor,
      "strokeOpacity" => $this->config->strokeOpacity,
      "strokeWeight" => $this->config->strokeWeight,
      "units" => $this->config->units,
      "uploadMaxSize" => $this->config->uploadMaxSize,
      "version" => $this->config->version,
      "layers" => []
    ];
    foreach ($this->config->olLayers as $key => $val) {
      $resultConfig["layers"][$key] = $val;
    }
    $result["config"] = $resultConfig;

    return Response::success($result);
  }

  public function save(array $data): Response {
    $this->config->setFromArray($data);
    // FIXME: olLayers can be null?
    $olLayers = in_array('olLayers', $data) ? $data['olLayers'] : null;
    if (!is_null($olLayers)) {
      $this->config->olLayers = [];
      foreach ($olLayers as $json) {
        $obj = json_decode($json);
        if (json_last_error() === JSON_ERROR_NONE) {
          $this->config->olLayers[] = new Layer($obj->id, $obj->name, $obj->url, $obj->priority);
        }
      }
    }

    if ($this->config->save() === false) {
      return Response::internalServerError("servererror");//($lang["servererror"]);
    }
    return Response::success();
  }
}
