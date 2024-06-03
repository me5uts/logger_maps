<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Helper\Utils;

$hash = Utils::getString("hash", "");
if (!empty($hash)) {
  $hash = "#$hash";
}
$auth = new Auth();
$auth->logOutWithRedirect($hash);

?>
