<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Helper;

use uLogger\Component\Response;
use uLogger\Entity\Config;
use uLogger\Entity\Position;
use uLogger\Exception\ServerException;
use XMLWriter;

class Kml implements FileFormatInterface {

  private Config $config;
  private string $name;
  private int $trackId = 0;

  private float $factorSpeed = 1;
  private string $unitSpeed = 'km/h';
  private float $factorAltitude = 1;
  private string $unitAltitude = 'm';
  private float $factorDistance = 1;
  private string $unitDistance = 'km';

  /**
   * @param string $name
   * @param Config $config
   */
  public function __construct(string $name, Config $config) {
    $this->name = $name;
    $this->config = $config;

    $units = $this->config->units;

    if ($units === 'imperial') {
      $this->factorSpeed = 0.62; //kmh to mph
      $this->unitSpeed = 'mph';
      $this->factorAltitude = 3.28; // meters to feet
      $this->unitAltitude = 'ft';
      $this->factorDistance = 0.62; // km to miles
      $this->unitDistance = 'mi';
    }
  }

  /**
   * @param int $userId
   * @param string $filePath
   * @return array
   * @throws ServerException
   */
  public function import(int $userId, string $filePath): array {
    throw new ServerException('Not implemented');
  }

  /**
   * TODO Improve KML format (lang?)
   * @param Position[] $positions
   * @return string GPX file
   */
  public function export(array $positions): string {

    $this->trackId = $positions[0]->trackId;

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument('1.0', 'utf-8');
    $xml->startElement('kml');
    $xml->writeAttributeNs('xsi', 'schemaLocation', null, 'http://www.opengis.net/kml/2.2 http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd');
    $xml->writeAttributeNs('xmlns', 'xsi', null, 'http://www.w3.org/2001/XMLSchema-instance');
    $xml->writeAttribute('xmlns', 'http://www.opengis.net/kml/2.2');
    $xml->startElement('Document');
    $xml->writeElement('name', $this->name);
    $this->setStyles($xml);
    $style = '#redStyle'; // for first element
    $currentCount = 0;
    $totalMeters = 0;
    $totalSeconds = 0;
    $totalCount = count($positions);
    $coordinates = [];
    foreach ($positions as $position) {
      $distance = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
      $seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
      $prevPosition = $position;
      $totalMeters += $distance;
      $totalSeconds += $seconds;
      $coordinate = "$position->longitude,$position->latitude" . (!is_null($position->altitude) ? ",$position->altitude" : '');

      if(++$currentCount === $totalCount) { $style = '#greenStyle'; } // last element
      $description = $this->getDescription($position, $totalSeconds, $totalMeters, $currentCount, $totalCount);
      $this->addPoint($xml, $position, $description, $style, $coordinate);
      $style = '#grayStyle'; // other elements
      $coordinates[] = $coordinate;
    }
    $this->addLine($xml, $coordinates);

    $xml->endElement();
    $xml->endElement();
    $xml->endDocument();

    return $xml->outputMemory();
  }

  /**
   * Convert seconds to [day], hour, minute, second string
   *
   * @param int $s Number of seconds
   * @return string [d ]hhmmss
   */
  private function toHMS(int $s): string {
    $d = floor($s / 86400);
    $h = floor(($s % 86400) / 3600);
    $m = floor((($s % 86400) % 3600) / 60);
    $s = (($s % 86400) % 3600) % 60;
    return (($d > 0) ? "$d d " : '') . sprintf('%02d:%02d:%02d', $h, $m, $s);
  }

  public function getExportedName(): string {
    return "track$this->trackId.kml";
  }

  public function getMimeType(): string {
    return Response::TYPE_KML;
  }

  /**
   * @param Position $position
   * @param int $totalSeconds
   * @param int $totalMeters
   * @param int $currentCount
   * @param int $totalCount
   * @return string
   */
  private function getDescription(Position $position, int $totalSeconds, int $totalMeters, int $currentCount, int $totalCount): string {
    return "<div style=\"font-weight: bolder; padding-bottom: 10px; border-bottom: 1px solid gray;\">" .
      htmlspecialchars($position->userName) . '@' . htmlspecialchars($position->trackName) .
      '</div>' .
      '<div>' .
      "<div style=\"padding-top: 10px;\">" . date('Y-m-d H:i:s (e)', $position->timestamp) . '<br>' .
      (!is_null($position->comment) ? "<b>$position->comment</b><br>" : '') .
      (!is_null($position->speed) ? round($position->speed * 3.6 * $this->factorSpeed, 2) . " $this->unitSpeed<br>" : '') .
      (!is_null($position->altitude) ? '&#8597; ' . round($position->altitude * $this->factorAltitude) . " $this->unitAltitude<br>" : '') .
      'Σ ' . $this->toHMS($totalSeconds) . '<br>' .
      '&#8596; ' . round($totalMeters / 1000 * $this->factorDistance, 2) . " $this->unitDistance<br>" .
      '~ ' . (($totalSeconds !== 0) ? round($totalMeters / $totalSeconds * 3.6 * $this->factorSpeed, 2) : 0) . " $this->unitSpeed<br>" .
      '</div>' .
      "<div style=\"font-size: smaller; padding-top: 10px;\">$currentCount&#47;" . $totalCount . '</div>' .
      '</div>';
  }

  /**
   * Add kml marker style element
   *
   * @param XMLWriter $xml Writer object
   * @param string $name Color name
   * @param string $url Url
   */
  private function addStyle(XMLWriter $xml, string $name, string $url): void {
    $xml->startElement('Style');
    $xml->writeAttribute('id', "{$name}Style");
    $xml->startElement('IconStyle');
    $xml->writeAttribute('id', "{$name}Icon");
    $xml->startElement('Icon');
    $xml->writeElement('href', $url);
    $xml->endElement();
    $xml->endElement();
    $xml->endElement();
  }

  /**
   * @param XMLWriter $xml
   * @return void
   */
  private function setStyles(XMLWriter $xml): void {
    // line style
    $xml->startElement('Style');
    $xml->writeAttribute('id', 'lineStyle');
    $xml->startElement('LineStyle');
    $xml->writeElement('color', '7f0000ff');
    $xml->writeElement('width', '4');
    $xml->endElement();
    $xml->endElement();
    // marker styles
    $this->addStyle($xml, 'red', 'http://maps.google.com/mapfiles/markerA.png');
    $this->addStyle($xml, 'green', 'http://maps.google.com/mapfiles/marker_greenB.png');
    $this->addStyle($xml, 'gray', 'http://maps.gstatic.com/mapfiles/ridefinder-images/mm_20_gray.png');
  }

  /**
   * @param XMLWriter $xml
   * @param array $coordinates
   * @return void
   */
  private function addLine(XMLWriter $xml, array $coordinates): void {
    $xml->startElement('Placemark');
    $xml->writeAttribute('id', 'lineString');
    $xml->writeElement('styleUrl', '#lineStyle');
    $xml->startElement('LineString');
    $xml->writeElement('coordinates', implode("\n", $coordinates));
    $xml->endElement();
    $xml->endElement();
  }

  /**
   * @param XMLWriter $xml
   * @param Position $position
   * @param string $description
   * @param string $style
   * @param string $coordinate
   * @return void
   */
  private function addPoint(XMLWriter $xml, Position $position, string $description, string $style, string $coordinate): void {
    $xml->startElement('Placemark');
    $xml->writeAttribute('id', "point_$position->id");

    $xml->startElement('description');
    $xml->writeCData($description);
    $xml->endElement();
    $xml->writeElement('styleUrl', $style);
    $xml->startElement('Point');
    $xml->writeElement('coordinates', $coordinate);
    $xml->endElement();
    $xml->endElement();
  }
}
