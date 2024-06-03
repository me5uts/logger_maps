<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Component\Lang;
use uLogger\Entity\Config;
use uLogger\Entity\Layer;
use uLogger\Helper\Utils;

$config = Config::getInstance();
$lang = (new Lang($config))->getStrings();
$auth = new Auth();

if (!$auth->isAdmin()) {
  Utils::exitWithError($lang["notauthorized"]);
}

$olLayers = Utils::postArray('olLayers');

$data = [
  'map_api' => Utils::postString('mapApi'),
  'latitude' => Utils::postFloat('initLatitude'),
  'longitude' => Utils::postFloat('initLongitude'),
  'google_key' => Utils::postString('googleKey'),
  'require_auth' => Utils::postBool('requireAuth'),
  'public_tracks' => Utils::postBool('publicTracks'),
  'pass_lenmin' => Utils::postInt('passLenMin'),
  'pass_strength' => Utils::postInt('passStrength'),
  'interval_seconds' => Utils::postInt('interval'),
  'lang' => Utils::postString('lang'),
  'units' => Utils::postString('units'),
  'stroke_weight' => Utils::postInt('strokeWeight'),
  'stroke_color' => Utils::postString('strokeColor'),
  'stroke_opacity' => Utils::postFloat('strokeOpacity'),
  'color_normal' => Utils::postString('colorNormal'),
  'color_start' => Utils::postString('colorStart'),
  'color_stop' => Utils::postString('colorStop'),
  'color_extra' => Utils::postString('colorExtra'),
  'color_hilite' => Utils::postString('colorHilite'),
  'upload_maxsize' => Utils::postInt('uploadMaxSize')
];

$config->setFromArray($data);
if (!is_null($olLayers)) {
  $config->olLayers = [];
  foreach ($olLayers as $json) {
    $obj = json_decode($json);
    if (json_last_error() === JSON_ERROR_NONE) {
      $config->olLayers[] = new Layer($obj->id, $obj->name, $obj->url, $obj->priority);
    }
  }
}

if ($config->save() === false) {
  Utils::exitWithError($lang["servererror"]);
}
Utils::exitWithSuccess();
