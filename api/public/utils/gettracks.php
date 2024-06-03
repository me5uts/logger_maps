<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Entity\Config;
use uLogger\Entity\Track;
use uLogger\Helper\Utils;

$auth = new Auth();
$config = Config::getInstance();

$userId = Utils::getInt('userid');

$tracksArr = [];
if ($userId && $auth->hasReadAccess($userId)) {
  $tracksArr = Track::getAll($userId);
}

$result = [];
if ($tracksArr === false) {
  $result = [ "error" => true ];
} else if (!empty($tracksArr)) {
  foreach ($tracksArr as $track) {
    $result[] = [ "id" => $track->id, "name" => $track->name ];
  }
}
header("Content-type: application/json");
echo json_encode($result);
?>
