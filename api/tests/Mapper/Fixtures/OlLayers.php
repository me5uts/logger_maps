<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper\Fixtures;

class OlLayers {
  /** @var string Table name */
  public string $table = 'ol_layers';

  /** @var array Records */
  public array $records = [
    [
      'id' => 1,
      'name' => 'layer1',
      'url' => 'https://testUrl',
      'priority' => 0
    ],
  ];
}
