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
use uLogger\Entity\Track;
use uLogger\Exception\GpxParseException;
use uLogger\Exception\ServerException;
use XMLWriter;

class Gpx implements FileFormatInterface {

  private string $name;
  private int $trackId = 0;
  private Config $config;

  /**
   * @param string $name
   * @param Config $config
   */
  public function __construct(string $name, Config $config) {
    $this->name = $name;
    $this->config = $config;
  }

  /**
   * TODO: create and return Tracks and Positions
   * @param int $userId Track owner ID
   * @param string $filePath Imported file path
   * @return Track[] Imported tracks metadata
   * @throws GpxParseException
   * @throws ServerException
   */
  public function import(int $userId, string $filePath): array {

    libxml_use_internal_errors(true);
    /** @noinspection SimpleXmlLoadFileUsageInspection */
    $gpx = simplexml_load_file($filePath);

    if ($gpx === false) {
      $message = "iparsefailure";
      $parserMessages = [];
      foreach (libxml_get_errors() as $parseError) {
        $parserMessages[] = $parseError->message;
      }
      $parserMessage = implode(", ", $parserMessages);
      if (!empty($parserMessage)) {
        $message .= ": $parserMessage";
      }
      throw new GpxParseException($message);
    } elseif ($gpx->getName() !== "gpx") {
      throw new GpxParseException("iparsefailure");
    } elseif (empty($gpx->trk)) {
      throw new GpxParseException("idatafailure");
    }

    $tracks = [];
    foreach ($gpx->trk as $trk) {
      $trackName = empty($trk->name) ? $this->name : (string) $trk->name;
      $metaName = empty($gpx->metadata->name) ? null : (string) $gpx->metadata->name;
      $trackId = Track::add($userId, $trackName, $metaName);
      if ($trackId === false) {
        throw new ServerException("servererror");
      }
      $track = new Track($trackId);
      $posCnt = 0;

      foreach ($trk->trkseg as $segment) {
        foreach ($segment->trkpt as $point) {
          if (!isset($point["lat"], $point["lon"])) {
            $track->delete();
            throw new GpxParseException("iparsefailure");
          }
          $time = isset($point->time) ? strtotime((string) $point->time) : 1;
          $altitude = isset($point->ele) ? (double) $point->ele : null;
          $comment = !empty($point->desc) ? (string) $point->desc : null;
          $speed = null;
          $bearing = null;
          $accuracy = null;
          $provider = "gps";
          if (!empty($point->extensions)) {
            // parse ulogger extensions
            $ext = $point->extensions->children('ulogger', true);
            if (count($ext->speed)) {
              $speed = (double) $ext->speed;
            }
            if (count($ext->bearing)) {
              $bearing = (double) $ext->bearing;
            }
            if (count($ext->accuracy)) {
              $accuracy = (int) $ext->accuracy;
            }
            if (count($ext->provider)) {
              $provider = (string) $ext->provider;
            }
          }
          $ret = $track->addPosition($userId,
            $time, (double) $point["lat"], (double) $point["lon"], $altitude,
            $speed, $bearing, $accuracy, $provider, $comment);
          if ($ret === false) {
            $track->delete();
            throw new ServerException("servererror");
          }
          $posCnt++;
        }
      }
      if ($posCnt) {
        array_unshift($tracks, $track);
      } else {
        $track->delete();
      }
    }

    return $tracks;
  }

  /**
   * @param Position[] $positions
   * @return string GPX file
   */
  public function export(array $positions): string {

    $this->trackId = $positions[0]->trackId;

    $creatorVersion = $this->config->version;

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument("1.0", "utf-8");
    $xml->startElement("gpx");
    $xml->writeAttribute("xmlns", "http://www.topografix.com/GPX/1/1");
    $xml->writeAttributeNs("xsi", "schemaLocation", null, "http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd https://github.com/bfabiszewski/ulogger-android/1 https://raw.githubusercontent.com/bfabiszewski/ulogger-server/master/scripts/gpx_extensions1.xsd");
    $xml->writeAttributeNs("xmlns", "xsi", null, "http://www.w3.org/2001/XMLSchema-instance");
    $xml->writeAttributeNs("xmlns", "ulogger", null, "https://github.com/bfabiszewski/ulogger-android/1");
    $xml->writeAttribute("creator", "μlogger-server " . $creatorVersion);
    $xml->writeAttribute("version", "1.1");
    $xml->startElement("metadata");
    $xml->writeElement("name", $this->name);
    $xml->writeElement("time", gmdate("Y-m-d\TH:i:s\Z", $positions[0]->timestamp));
    $xml->endElement();
    $xml->startElement("trk");
    $xml->writeElement("name", $this->name);
    $xml->startElement("trkseg");
    $positionNumber = 0;

    foreach ($positions as $position) {
      $xml->startElement("trkpt");
      $xml->writeAttribute("lat", (string) $position->latitude);
      $xml->writeAttribute("lon", (string) $position->longitude);
      if (!is_null($position->altitude)) {
        $xml->writeElement("ele", (string) $position->altitude);
      }
      $xml->writeElement("time", gmdate("Y-m-d\TH:i:s\Z", $position->timestamp));
      $xml->writeElement("name", (string) ++$positionNumber);
      if (!is_null($position->comment)) {
        $xml->startElement("desc");
        $xml->writeCData($position->comment);
        $xml->endElement();
      }
      if (!is_null($position->speed) || !is_null($position->bearing) || !is_null($position->accuracy) || !is_null($position->provider)) {
        $xml->startElement("extensions");

        if (!is_null($position->speed)) {
          $xml->writeElementNS("ulogger", "speed", null, (string) round($position->speed, 2));
        }
        if (!is_null($position->bearing)) {
          $xml->writeElementNS("ulogger", "bearing", null, (string) round($position->bearing, 2));
        }
        if (!is_null($position->accuracy)) {
          $xml->writeElementNS("ulogger", "accuracy", null, (string) $position->accuracy);
        }
        if (!is_null($position->provider)) {
          $xml->writeElementNS("ulogger", "provider", null, (string) $position->provider);
        }
        $xml->endElement();
      }
      $xml->endElement();
    }
    $xml->endElement();
    $xml->endElement();
    $xml->endElement();
    $xml->endDocument();

    return $xml->outputMemory();
  }

  public function getExportedName(): string {
    return "track$this->trackId.gpx";
  }

  public function getMimeType(): string {
    return Response::TYPE_GPX;
  }
}
