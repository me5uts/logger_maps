<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use uLogger\Exception\DatabaseException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Utils;
use uLogger\Mapper;
use uLogger\Mapper\Column;
use uLogger\Mapper\MapperFactory;

/**
 * Handles config values
 */
class Config {

  /**
   * @var string Version number
   */
  public string $version = "2.0-beta";

  /**
   * @var string Default map drawing framework
   */
  #[Column(name: "map_api")]
  public string $mapApi = "openlayers";

  /**
   * @var string|null Google Maps key
   */
  #[Column(name: "google_key")]
  public ?string $googleKey = null;

  /**
   * @var Layer[] OpenLayers extra map layers
   */
  public array $olLayers = [];

  /**
   * @var float Default latitude for initial map
   */
  #[Column(name: "latitude")]
  public float $initLatitude = 52.23;
  /**
   * @var float Default longitude for initial map
   */
  #[Column(name: "longitude")]
  public float $initLongitude = 21.01;

  /**
   * @var bool Require login/password authentication
   */
  #[Column(name: "require_auth")]
  public bool $requireAuthentication = true;

  /**
   * @var bool All users tracks are visible to authenticated user
   */
  #[Column(name: "public_tracks")]
  public bool $publicTracks = false;

  /**
   * @var int Minimum required length of user password
   */
  #[Column(name: "pass_lenmin")]
  public int $passLenMin = 10;

  /**
   * @var int Required strength of user password
   * 0 = no requirements,
   * 1 = require mixed case letters (lower and upper),
   * 2 = require mixed case and numbers
   * 3 = require mixed case, numbers and non-alphanumeric characters
   */
  #[Column(name: "pass_strength")]
  public int $passStrength = 2;

  /**
   * @var int Default interval in seconds for live auto reload
   */
  #[Column(name: "interval_seconds")]
  public int $interval = 10;

  /**
   * @var string Default language code
   */
  #[Column]
  public string $lang = "en";

  /**
   * @var string Default units
   */
  #[Column]
  public string $units = "metric";

  /**
   * @var int Stroke weight
   */
  #[Column(name: "stroke_weight")]
  public int $strokeWeight = 2;
  /**
   * @var string Stroke color
   */
  #[Column(name: "stroke_color")]
  public string $strokeColor = "#ff0000";
  /**
   * @var float Stroke opacity
   */
  #[Column(name: "stroke_opacity")]
  public float $strokeOpacity = 1.0;
  /**
   * @var string Stroke color
   */
  #[Column(name: "color_normal")]
  public string $colorNormal = "#ffffff";
  /**
   * @var string Stroke color
   */
  #[Column(name: "color_start")]
  public string $colorStart = "#55b500";
  /**
   * @var string Stroke color
   */
  #[Column(name: "color_stop")]
  public string $colorStop = "#ff6a00";
  /**
   * @var string Stroke color
   */
  #[Column(name: "color_extra")]
  public string $colorExtra = "#cccccc";
  /**
   * @var string Stroke color
   */
  #[Column(name: "color_hilite")]
  public string $colorHilite = "#feff6a";
  /**
   * @var int Maximum size of uploaded files in bytes.
   * Will be adjusted to system maximum upload size
   */
  #[Column(name: "upload_maxsize")]
  public int $uploadMaxSize = 5242880;
//
//  public function __construct(bool $useDatabase = true) {
//    if ($useDatabase) {
//      $this->setFromDatabase();
//    }
//    $this->setFromCookies();
//  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   */
  public static function createFromMapper(MapperFactory $factory): Config {
    /** @var Mapper\Config $mapper */
    $mapper = $factory->getMapper(Mapper\Config::class);
    return $mapper->fetch();
  }

//  /**
//   * Read config values stored in cookies
//   */
//  private function setFromCookies(): void {
//    if (isset($_COOKIE["ulogger_api"])) { $this->mapApi = $_COOKIE["ulogger_api"]; }
//    if (isset($_COOKIE["ulogger_lang"])) { $this->lang = $_COOKIE["ulogger_lang"]; }
//    if (isset($_COOKIE["ulogger_units"])) { $this->units = $_COOKIE["ulogger_units"]; }
//    if (isset($_COOKIE["ulogger_interval"])) { $this->interval = $_COOKIE["ulogger_interval"]; }
//  }


  /**
   * Check if given password matches user's one
   *
   * @param string $password Password
   * @return bool True if matches, false otherwise
   */
  public function validPassStrength(string $password): bool {
    return preg_match($this->passRegex(), $password) === 1;
  }

  /**
   * Regex to test if password matches strength and length requirements.
   * Valid for both php and javascript
   * @return string
   */
  public function passRegex(): string {
    $regex = "";
    if ($this->passStrength > 0) {
      // lower and upper case
      $regex .= "(?=.*[a-z])(?=.*[A-Z])";
    }
    if ($this->passStrength > 1) {
      // digits
      $regex .= "(?=.*[0-9])";
    }
    if ($this->passStrength > 2) {
      // not latin, not digits
      $regex .= "(?=.*[^a-zA-Z0-9])";
    }
    if ($this->passLenMin > 0) {
      $regex .= "(?=.{" . $this->passLenMin . ",})";
    }
    if (empty($regex)) {
      $regex = ".*";
    }
    return "/" . $regex . "/";
  }

  /**
   * Set config values from array
   * @param array $arr
   */
  public function setFromArray(array $arr): void {

    if (!empty($arr['map_api'])) {
      $this->mapApi = $arr['map_api'];
    }
    if (isset($arr['latitude']) && is_numeric($arr['latitude'])) {
      $this->initLatitude = (float) $arr['latitude'];
    }
    if (isset($arr['longitude']) && is_numeric($arr['longitude'])) {
      $this->initLongitude = (float) $arr['longitude'];
    }
    if (isset($arr['google_key'])) {
      $this->googleKey = $arr['google_key'];
    }
    if (isset($arr['require_auth']) && (is_numeric($arr['require_auth']) || is_bool($arr['require_auth']))) {
      $this->requireAuthentication = (bool) $arr['require_auth'];
    }
    if (isset($arr['public_tracks']) && (is_numeric($arr['public_tracks']) || is_bool($arr['public_tracks']))) {
      $this->publicTracks = (bool) $arr['public_tracks'];
    }
    if (isset($arr['pass_lenmin']) && is_numeric($arr['pass_lenmin'])) {
      $this->passLenMin = (int) $arr['pass_lenmin'];
    }
    if (isset($arr['pass_strength']) && is_numeric($arr['pass_strength'])) {
      $this->passStrength = (int) $arr['pass_strength'];
    }
    if (isset($arr['interval_seconds']) && is_numeric($arr['interval_seconds'])) {
      $this->interval = (int) $arr['interval_seconds'];
    }
    if (!empty($arr['lang'])) {
      $this->lang = $arr['lang'];
    }
    if (!empty($arr['units'])) {
      $this->units = $arr['units'];
    }
    if (isset($arr['stroke_weight']) && is_numeric($arr['stroke_weight'])) {
      $this->strokeWeight = (int) $arr['stroke_weight'];
    }
    if (!empty($arr['stroke_color'])) {
      $this->strokeColor = $arr['stroke_color'];
    }
    if (isset($arr['stroke_opacity']) && is_numeric($arr['stroke_opacity'])) {
      $this->strokeOpacity = (float) $arr['stroke_opacity'];
    }
    if (!empty($arr['color_normal'])) {
      $this->colorNormal = $arr['color_normal'];
    }
    if (!empty($arr['color_start'])) {
      $this->colorStart = $arr['color_start'];
    }
    if (!empty($arr['color_stop'])) {
      $this->colorStop = $arr['color_stop'];
    }
    if (!empty($arr['color_extra'])) {
      $this->colorExtra = $arr['color_extra'];
    }
    if (!empty($arr['color_hilite'])) {
      $this->colorHilite = $arr['color_hilite'];
    }
    if (isset($arr['upload_maxsize']) && is_numeric($arr['upload_maxsize'])) {
      $this->uploadMaxSize = (int) $arr['upload_maxsize'];
      $this->setUploadLimit();
    }
    if (!$this->requireAuthentication) {
      // tracks must be public if we don't require authentication
      $this->publicTracks = true;
    }
  }
  public function setFromConfig(Config $config): void {
    $this->mapApi = $config->mapApi;
    $this->initLatitude = $config->initLatitude;
    $this->initLongitude = $config->initLongitude;
    $this->googleKey = $config->googleKey;
    $this->requireAuthentication = $config->requireAuthentication;
    $this->publicTracks = $config->publicTracks;
    $this->passLenMin = $config->passLenMin;
    $this->passStrength = $config->passStrength;
    $this->interval = $config->interval;
    $this->lang = $config->lang;
    $this->units = $config->units;
    $this->strokeWeight = $config->strokeWeight;
    $this->strokeColor = $config->strokeColor;
    $this->strokeOpacity = $config->strokeOpacity;
    $this->colorNormal = $config->colorNormal;
    $this->colorStart = $config->colorStart;
    $this->colorStop = $config->colorStop;
    $this->colorExtra = $config->colorExtra;
    $this->colorHilite = $config->colorHilite;
    $this->uploadMaxSize = $config->uploadMaxSize;
  }

  /**
   * Adjust uploadMaxSize to system limits
   */
  public function setUploadLimit(): void {
    $limit = Utils::getSystemUploadLimit();
    if ($this->uploadMaxSize <= 0 || $this->uploadMaxSize > $limit) {
      $this->uploadMaxSize = $limit;
    }
  }
}

?>
