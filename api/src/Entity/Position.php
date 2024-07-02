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
class Position extends AbstractEntity {
  /** @var int|null Position id */
  #[Column]
  #[JsonField]
  public ?int $id = null;
  /** @var int Unix time stamp */
  #[Column(name: 'tstamp')]
  #[JsonField]
  public int $timestamp;
  /** @var int User id */
  #[Column(name: 'user_id')]
  #[JsonField]
  public int $userId;
  /** @var string|null User login */
  #[Column(name: 'login')]
  #[JsonField]
  public ?string $userName = null;
  /** @var int Track id */
  #[Column(name: 'track_id')]
  #[JsonField]
  public int $trackId;
  /** @var string|null Track name */
  #[Column(name: 'name')]
  #[JsonField]
  public ?string $trackName = null;
  /** @var float Latitude */
  #[Column]
  #[JsonField]
  public float $latitude;
  /** @var float Longitude */
  #[Column]
  #[JsonField]
  public float $longitude;
  /** @var ?float Altitude */
  #[Column]
  #[JsonField]
  public ?float $altitude = null;
  /** @var ?float Speed */
  #[Column]
  #[JsonField]
  public ?float $speed = null;
  /** @var ?float Bearing */
  #[Column]
  #[JsonField]
  public ?float $bearing = null;
  /** @var ?int Accuracy */
  #[Column]
  #[JsonField]
  public ?int $accuracy = null;
  /** @var ?string Provider */
  #[Column]
  #[JsonField]
  public ?string $provider = null;
  /** @var ?string Comment */
  #[Column]
  #[JsonField]
  public ?string $comment = null;
  /** @var ?string Image path */
  #[Column]
  #[JsonField]
  public ?string $image = null;
  /** @var int|null Distance from track beginning */
  #[JsonField]
  public ?int $meters = 0;
  /** @var int|null Time from track beginning */
  #[JsonField]
  public ?int $seconds = 0;

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

}

?>
