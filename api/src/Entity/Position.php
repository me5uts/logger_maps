<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use uLogger\Mapper\Column;

/**
 * Positions handling
 */
class Position {
  /** @var int|null Position id */
  #[Column]
  public ?int $id = null;
  /** @var int Unix time stamp */
  #[Column(name: 'tstamp')]
  public int $timestamp;
  /** @var int User id */
  #[Column(name: 'user_id')]
  public int $userId;
  /** @var string|null User login */
  #[Column(name: 'login')]
  public ?string $userLogin = null;
  /** @var int Track id */
  #[Column(name: 'track_id')]
  public int $trackId;
  /** @var string|null Track name */
  #[Column(name: 'name')]
  public ?string $trackName = null;
  /** @var float Latitude */
  #[Column]
  public float $latitude;
  /** @var float Longitude */
  #[Column]
  public float $longitude;
  /** @var ?float Altitude */
  #[Column]
  public ?float $altitude = null;
  /** @var ?float Speed */
  #[Column]
  public ?float $speed = null;
  /** @var ?float Bearing */
  #[Column]
  public ?float $bearing = null;
  /** @var ?int Accuracy */
  #[Column]
  public ?int $accuracy = null;
  /** @var ?string Provider */
  #[Column]
  public ?string $provider = null;
  /** @var ?string Comment */
  #[Column]
  public ?string $comment = null;
  /** @var ?string Image path */
  #[Column]
  public ?string $image = null;

  /**
   * @param int $timestamp
   * @param int $userId
   * @param int $trackId
   * @param float $latitude
   * @param float $longitude
   */
  public function __construct(int $timestamp, int $userId, int $trackId, float $latitude, float $longitude) {
    $this->timestamp = $timestamp;
    $this->userId = $userId;
    $this->trackId = $trackId;
    $this->latitude = $latitude;
    $this->longitude = $longitude;
  }


  /**
   * Has image
   *
   * @return bool True if position has image
   */
  public function hasImage(): bool {
    return !empty($this->image);
  }

 /**
  * Calculate distance to target point using haversine formula
  *
  * @param Position $target Target position
  * @return int Distance in meters
  */
  public function distanceTo(Position $target): int {
    $lat1 = deg2rad($this->latitude);
    $lon1 = deg2rad($this->longitude);
    $lat2 = deg2rad($target->latitude);
    $lon2 = deg2rad($target->longitude);
    $latD = $lat2 - $lat1;
    $lonD = $lon2 - $lon1;
    $bearing = 2 * asin(sqrt((sin($latD / 2) ** 2) + cos($lat1) * cos($lat2) * (sin($lonD / 2) ** 2)));
    return (int) round($bearing * 6371000);
  }

 /**
  * Calculate time elapsed since target point
  *
  * @param Position $target Target position
  * @return int Number of seconds
  */
  public function secondsTo(Position $target): int {
    return $this->timestamp - $target->timestamp;
  }

//  /**
//   * Set position from array. Array should not be empty
//   * @param array $row
//   */
//  private function setFromArray(array $row): void {
//    $this->id = (int) $row['id'];
//    $this->timestamp = (int) $row['tstamp'];
//    $this->userId = (int) $row['user_id'];
//    $this->userLogin = $row['login'];
//    $this->trackId = (int) $row['track_id'];
//    $this->trackName = $row['name'];
//    $this->latitude = (float) $row['latitude'];
//    $this->longitude = (float) $row['longitude'];
//    $this->altitude = is_null($row['altitude']) ? null : (float) $row['altitude'];
//    $this->speed = is_null($row['speed']) ? null : (float) $row['speed'];
//    $this->bearing = is_null($row['bearing']) ? null : (float) $row['bearing'];
//    $this->accuracy = is_null($row['accuracy']) ? null : (int) $row['accuracy'];
//    $this->provider = $row['provider'];
//    $this->comment = $row['comment'];
//    $this->image = $row['image'];
//  }

  /**
   * @param Position $position
   * @param int $meters
   * @param int $seconds
   * @return array
   */
  public static function getArray(Position $position, int $meters = 0, int $seconds = 0): array {
    return [
      "id" => $position->id,
      "latitude" => $position->latitude,
      "longitude" => $position->longitude,
      "altitude" => ($position->altitude) ? round($position->altitude) : $position->altitude,
      "speed" => $position->speed,
      "bearing" => $position->bearing,
      "timestamp" => $position->timestamp,
      "accuracy" => $position->accuracy,
      "provider" => $position->provider,
      "comment" => $position->comment,
      "image" => $position->image,
      "username" => $position->userLogin,
      "trackid" => $position->trackId,
      "trackname" => $position->trackName,
      "meters" => $meters,
      "seconds" => $seconds
    ];
  }
}

?>
