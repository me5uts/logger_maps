<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper\Fixtures;

class Config {
  /** @var string Table name */
  public string $table = 'config';

  /** @var array Records */
  public array $records = [
    [ 'name' => 'color_extra', 'value' => 's:11:"test_color1";' ],
    [ 'name' => 'color_hilite', 'value' => 's:11:"test_color2";' ],
    [ 'name' => 'color_normal', 'value' => 's:11:"test_color3";' ],
    [ 'name' => 'color_start', 'value' => 's:11:"test_color4";' ],
    [ 'name' => 'color_stop', 'value' => 's:11:"test_color5";' ],
    [ 'name' => 'google_key', 'value' => 's:8:"test_key";' ],
    [ 'name' => 'interval_seconds', 'value' => 'i:0;' ],
    [ 'name' => 'lang', 'value' => 's:2:"pl";' ],
    [ 'name' => 'latitude', 'value' => 'd:53.1;' ],
    [ 'name' => 'longitude', 'value' => 'd:21.1;' ],
    [ 'name' => 'map_api', 'value' => 's:10:"openlayers";' ],
    [ 'name' => 'pass_lenmin', 'value' => 'i:0;' ],
    [ 'name' => 'pass_strength', 'value' => 'i:0;' ],
    [ 'name' => 'public_tracks', 'value' => 'b:0;' ],
    [ 'name' => 'require_auth', 'value' => 'b:0;' ],
    [ 'name' => 'stroke_color', 'value' => 's:7:"#000000";' ],
    [ 'name' => 'stroke_opacity', 'value' => 'd:0;' ],
    [ 'name' => 'stroke_weight', 'value' => 'i:0;' ],
    [ 'name' => 'units', 'value' => 's:6:"metric";' ],
    [ 'name' => 'upload_maxsize', 'value' => 'i:1234;' ]
  ];
}
