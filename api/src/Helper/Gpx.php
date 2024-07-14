<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Helper;

use SimpleXMLElement;
use uLogger\Component\Response;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\GpxParseException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;
use XMLWriter;

class Gpx implements FileFormatInterface {

  private string $name;
  private int $trackId = 0;
  private Entity\Config $config;
  /** @var Mapper\Position */
  private Mapper\Position $mapperPosition;
  /** @var Mapper\Track */
  private Mapper\Track $mapperTrack;

  /**
   * @param string $name
   * @param Entity\Config $config
   * @param MapperFactory $factory
   * @throws ServerException
   */
  public function __construct(string $name, Entity\Config $config, MapperFactory $factory) {
    $this->name = $name;
    $this->config = $config;
    $this->mapperTrack = $factory->getMapper(Mapper\Track::class);
    $this->mapperPosition = $factory->getMapper(Mapper\Position::class);
  }

  /**
   * TODO: create and return Tracks and Positions
   * @param int $userId Track owner ID
   * @param string $filePath Imported file path
   * @return Entity\Track[] Imported tracks metadata
   * @throws GpxParseException
   * @throws ServerException
   * @throws DatabaseException
   */
  public function import(int $userId, string $filePath): array {

    $gpx = $this->parseGpxFile($filePath);

    $tracks = [];
    foreach ($gpx->trk as $trk) {

      $trackName = empty($trk->name) ? $this->name : (string) $trk->name;
      $trackComment = empty($gpx->metadata->name) ? null : (string) $gpx->metadata->name;
      $track = new Entity\Track($userId, $trackName, $trackComment);

      try {
        $this->mapperTrack->create($track);

        $posCnt = 0;
        foreach ($trk->trkseg as $segment) {
          foreach ($segment->trkpt as $point) {
            $this->savePosition($point, $track, $userId);
            $posCnt++;
          }
        }
        if ($posCnt) {
          array_unshift($tracks, $track);
        } else {
          $this->deleteTrack($track);
        }
      } catch (DatabaseException $e) {
        try {
          $this->deleteTrack($track);
        } catch (DatabaseException) { /* ignore */ }
        throw new DatabaseException($e->getMessage());
      }
    }

    return $tracks;
  }

  /**
   * @param Entity\Position[] $positions
   * @return string GPX file
   */
  public function export(array $positions): string {

    $this->trackId = $positions[0]->trackId;

    $creatorVersion = $this->config->version;

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument("1.0", "utf-8");
    {
      $xml->startElement("gpx");
      $xml->writeAttribute("xmlns", "http://www.topografix.com/GPX/1/1");
      $xml->writeAttributeNs("xsi", "schemaLocation", null, "http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd https://github.com/bfabiszewski/ulogger-android/1 https://raw.githubusercontent.com/bfabiszewski/ulogger-server/master/scripts/gpx_extensions1.xsd");
      $xml->writeAttributeNs("xmlns", "xsi", null, "http://www.w3.org/2001/XMLSchema-instance");
      $xml->writeAttributeNs("xmlns", "ulogger", null, "https://github.com/bfabiszewski/ulogger-android/1");
      $xml->writeAttribute("creator", "μlogger-server " . $creatorVersion);
      $xml->writeAttribute("version", "1.1");
      {
        $xml->startElement("metadata");
        $xml->writeElement("name", $this->name);
        $xml->writeElement("time", gmdate("Y-m-d\TH:i:s\Z", $positions[0]->timestamp));
        $xml->endElement();
      }
      $this->trackToGpx($xml, $positions);
      $xml->endElement();
    }
    $xml->endDocument();
    return $xml->outputMemory();
  }

  public function getExportedName(): string {
    return "track$this->trackId.gpx";
  }

  public function getMimeType(): string {
    return Response::TYPE_GPX;
  }

  /**
   * @param Entity\Track $track
   * @return void
   * @throws DatabaseException
   * @throws ServerException
   */
  private function deleteTrack(Entity\Track $track): void {
    if ($track->id) {
      $this->mapperPosition->deleteAll($track->userId, $track->id);
      $this->mapperTrack->delete($track);
    }
  }

  /**
   * @param SimpleXMLElement|null $point
   * @param Entity\Track $track
   * @param int $userId
   * @return void
   * @throws DatabaseException
   * @throws GpxParseException
   * @throws ServerException
   */
  private function savePosition(?SimpleXMLElement $point, Entity\Track $track, int $userId): void {
    if (!isset($point["lat"], $point["lon"])) {
      $this->deleteTrack($track);
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
    $position = new Entity\Position(
      timestamp: $time,
      userId: $userId,
      trackId: $track->id,
      latitude: (double) $point["lat"],
      longitude: (double) $point["lon"]
    );
    $position->altitude = $altitude;
    $position->speed = $speed;
    $position->bearing = $bearing;
    $position->accuracy = $accuracy;
    $position->provider = $provider;
    $position->comment = $comment;
    $this->mapperPosition->create($position);
  }

  /**
   * @param string $filePath
   * @return SimpleXMLElement
   * @throws GpxParseException
   */
  private function parseGpxFile(string $filePath): SimpleXMLElement {
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
    return $gpx;
  }

  /**
   * @param XMLWriter $xml
   * @param Entity\Position $position
   * @param int $positionNumber
   * @return void
   */
  private function positionToGpx(XMLWriter $xml, Entity\Position $position, int $positionNumber): void {
    $xml->startElement("trkpt");
    $xml->writeAttribute("lat", (string) $position->latitude);
    $xml->writeAttribute("lon", (string) $position->longitude);
    if (!is_null($position->altitude)) {
      $xml->writeElement("ele", (string) $position->altitude);
    }
    $xml->writeElement("time", gmdate("Y-m-d\TH:i:s\Z", $position->timestamp));
    $xml->writeElement("name", (string) $positionNumber);
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

  /**
   * @param XMLWriter $xml
   * @param array $positions
   * @return void
   */
  private function trackToGpx(XMLWriter $xml, array $positions): void {
    $xml->startElement("trk");
    $xml->writeElement("name", $this->name);

    $xml->startElement("trkseg");
    $positionNumber = 0;
    foreach ($positions as $position) {
      $this->positionToGpx($xml, $position, ++$positionNumber);
    }
    $xml->endElement();

    $xml->endElement();
  }
}
