<?php
declare(strict_types = 1);
/**
 * μlogger
 *
 * Copyright(C) 2020 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

require_once('../../vendor/autoload.php');

use uLogger\Controller\Auth;
use uLogger\Controller\Config;
use uLogger\Controller\Lang;
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
