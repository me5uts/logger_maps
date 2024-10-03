<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper\Fixtures;

class Tracks {
  /** @var string Table name */
  public string $table = 'tracks';

  /** @var array Records */
  public array $records = [
    [
      'id' => 1,
      'user_id' => 1,
      'name' => 'track1',
      'comment' => null
    ],
    [
      'id' => 2,
      'user_id' => 2,
      'name' => 'track2',
      'comment' => null
    ],
    [
      'id' => 3,
      'user_id' => 1,
      'name' => 'track3',
      'comment' => null
    ],
  ];
}
