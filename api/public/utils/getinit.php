<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Controller\Auth;
use uLogger\Controller\Config;
use uLogger\Controller\Lang;

$auth = new Auth();
$config = Config::getInstance();
$langStrings = (new Lang($config))->getStrings();

$result = [];
$resultAuth = [
  "isAdmin" => $auth->isAdmin(),
  "isAuthenticated" => $auth->isAuthenticated()
];
if ($auth->isAuthenticated()) {
  $resultAuth["userId"] = $auth->user->id;
  $resultAuth["userLogin"] = $auth->user->login;
}

$resultConfig = [
  "colorExtra" => $config->colorExtra,
  "colorHilite" => $config->colorHilite,
  "colorNormal" => $config->colorNormal,
  "colorStart" => $config->colorStart,
  "colorStop" => $config->colorStop,
  "googleKey" => $config->googleKey,
  "initLatitude" => $config->initLatitude,
  "initLongitude" => $config->initLongitude,
  "interval" => $config->interval,
  "lang" => $config->lang,
  "mapApi" => $config->mapApi,
  "passLenMin" => $config->passLenMin,
  "passStrength" => $config->passStrength,
  "publicTracks" => $config->publicTracks,
  "requireAuth" => $config->requireAuthentication,
  "strokeColor" => $config->strokeColor,
  "strokeOpacity" => $config->strokeOpacity,
  "strokeWeight" => $config->strokeWeight,
  "units" => $config->units,
  "uploadMaxSize" => $config->uploadMaxSize,
  "version" => $config->version,
  "layers" => []
];
foreach ($config->olLayers as $key => $val) {
  $resultConfig["layers"][$key] = $val;
}

$resultLang = [
  "langArr" => Lang::getLanguages()
];
foreach ($langStrings as $key => $val) {
  $resultLang[$key] = $val;
}

$result["auth"] = $resultAuth;
$result["config"] = $resultConfig;
$result["lang"] = $resultLang;

header("Content-type: application/json");
echo json_encode($result);

?>
