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

  /**
   * @param string $name
   * @param Config $config
   */
  public function __construct(string $name, Config $config) {
    $this->name = $name;
    $this->config = $config;
  }

  /**
   * @param int $userId
   * @param string $filePath
   * @return array
   * @throws ServerException
   */
  public function import(int $userId, string $filePath): array {
   throw new ServerException("Not implemented");
  }

  /**
   * TODO Improve KML format (lang?)
   * @param Position[] $positions
   * @return string GPX file
   */
  public function export(array $positions): string {

    $this->trackId = $positions[0]->trackId;

    $units = $this->config->units;

    if ($units === "imperial") {
      $factor_kmh = 0.62; //to mph
      $unit_kmh = "mph";
      $factor_m = 3.28; // to feet
      $unit_m = "ft";
      $factor_km = 0.62; // to miles
      $unit_km = "mi";
    } else {
      $factor_kmh = 1;
      $unit_kmh = "km/h";
      $factor_m = 1;
      $unit_m = "m";
      $factor_km = 1;
      $unit_km = "km";
    }

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument("1.0", "utf-8");
    $xml->startElement("kml");
    $xml->writeAttributeNs("xsi", "schemaLocation", null, "http://www.opengis.net/kml/2.2 http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd");
    $xml->writeAttributeNs("xmlns", "xsi", null, "http://www.w3.org/2001/XMLSchema-instance");
    $xml->writeAttribute("xmlns", "http://www.opengis.net/kml/2.2");
    $xml->startElement("Document");
    $xml->writeElement("name", $this->name);
    // line style
    $xml->startElement("Style");
    $xml->writeAttribute("id", "lineStyle");
    $xml->startElement("LineStyle");
    $xml->writeElement("color", "7f0000ff");
    $xml->writeElement("width", "4");
    $xml->endElement();
    $xml->endElement();
    // marker styles
    $this->addStyle($xml, "red", "http://maps.google.com/mapfiles/markerA.png");
    $this->addStyle($xml, "green", "http://maps.google.com/mapfiles/marker_greenB.png");
    $this->addStyle($xml, "gray", "http://maps.gstatic.com/mapfiles/ridefinder-images/mm_20_gray.png");
    $style = "#redStyle"; // for first element
    $i = 0;
    $totalMeters = 0;
    $totalSeconds = 0;
    $coordinate = [];
    foreach ($positions as $position) {
      $distance = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
      $seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
      $prevPosition = $position;
      $totalMeters += $distance;
      $totalSeconds += $seconds;

      if(++$i === count($positions)) { $style = "#greenStyle"; } // last element
      $xml->startElement("Placemark");
      $xml->writeAttribute("id", "point_$position->id");
//      $description =
//        "<div style=\"font-weight: bolder; padding-bottom: 10px; border-bottom: 1px solid gray;\">" .
//        "{$lang["user"]}: " . htmlspecialchars($position->userLogin) . "<br>{$lang["track"]}: " . htmlspecialchars($position->trackName) .
//        "</div>" .
//        "<div>" .
//        "<div style=\"padding-top: 10px;\"><b>{$lang["time"]}:</b> " . date("Y-m-d H:i:s (e)", $position->timestamp) . "<br>" .
//        (!is_null($position->comment) ? "<b>$position->comment</b><br>" : "") .
//        (!is_null($position->speed) ? "<b>{$lang["speed"]}:</b> " . round($position->speed * 3.6 * $factor_kmh, 2) . " $unit_kmh<br>" : "") .
//        (!is_null($position->altitude) ? "<b>{$lang["altitude"]}:</b> " . round($position->altitude * $factor_m) . " $unit_m<br>" : "") .
//        "<b>{$lang["ttime"]}:</b> " . $this->toHMS($totalSeconds) . "<br>" .
//        "<b>{$lang["aspeed"]}:</b> " . (($totalSeconds !== 0) ? round($totalMeters / $totalSeconds * 3.6 * $factor_kmh, 2) : 0) . " $unit_kmh<br>" .
//        "<b>{$lang["tdistance"]}:</b> " . round($totalMeters / 1000 * $factor_km, 2) . " " . $unit_km . "<br></div>" .
//        "<div style=\"font-size: smaller; padding-top: 10px;\">" . sprintf($lang["pointof"], $i, count($positionsArr)) . "</div>" .
//        "</div>";
      $description =
        "<div style=\"font-weight: bolder; padding-bottom: 10px; border-bottom: 1px solid gray;\">" .
          htmlspecialchars($position->userLogin) . "@" . htmlspecialchars($position->trackName) .
        "</div>" .
        "<div>" .
          "<div style=\"padding-top: 10px;\">" . date("Y-m-d H:i:s (e)", $position->timestamp) . "<br>" .
          (!is_null($position->comment) ? "<b>$position->comment</b><br>" : "") .
          (!is_null($position->speed) ? round($position->speed * 3.6 * $factor_kmh, 2) . " $unit_kmh<br>" : "") .
          (!is_null($position->altitude) ? "&#8597; " . round($position->altitude * $factor_m) . " $unit_m<br>" : "") .
          "Σ " . $this->toHMS($totalSeconds) . "<br>" .
          "&#8596; " . round($totalMeters / 1000 * $factor_km, 2) . " $unit_km<br>" .
          "~ " . (($totalSeconds !== 0) ? round($totalMeters / $totalSeconds * 3.6 * $factor_kmh, 2) : 0) . " $unit_kmh<br>" .
          "</div>" .
          "<div style=\"font-size: smaller; padding-top: 10px;\">$i&#47;" . count($positions) . "</div>" .
        "</div>";
      $xml->startElement("description");
      $xml->writeCData($description);
      $xml->endElement();
      $xml->writeElement("styleUrl", $style);
      $xml->startElement("Point");
      $coordinate[$i] = "$position->longitude,$position->latitude" . (!is_null($position->altitude) ? ",$position->altitude" : "");
      $xml->writeElement("coordinates", $coordinate[$i]);
      $xml->endElement();
      $xml->endElement();
      $style = "#grayStyle"; // other elements
    }
    $coordinates = implode("\n", $coordinate);
    $xml->startElement("Placemark");
    $xml->writeAttribute("id", "lineString");
    $xml->writeElement("styleUrl", "#lineStyle");
    $xml->startElement("LineString");
    $xml->writeElement("coordinates", $coordinates);
    $xml->endElement();
    $xml->endElement();

    $xml->endElement();
    $xml->endElement();
    $xml->endDocument();

    return $xml->outputMemory();
  }

  /**
   * Add kml marker style element
   *
   * @param XMLWriter $xml Writer object
   * @param string $name Color name
   * @param string $url Url
   */
  private function addStyle(XMLWriter $xml, string $name, string $url): void {
    $xml->startElement("Style");
    $xml->writeAttribute("id", "{$name}Style");
    $xml->startElement("IconStyle");
    $xml->writeAttribute("id", "{$name}Icon");
    $xml->startElement("Icon");
    $xml->writeElement("href", $url);
    $xml->endElement();
    $xml->endElement();
    $xml->endElement();
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
    return (($d > 0) ? "$d d " : "") . sprintf("%02d:%02d:%02d", $h, $m, $s);
  }

  public function getExportedName(): string {
    return "track$this->trackId.kml";
  }

  public function getMimeType(): string {
    return Response::TYPE_KML;
  }
}
