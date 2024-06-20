<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Session;
use uLogger\Component\FileUpload;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity;
use uLogger\Entity\Config;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\GpxParseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Gpx;
use uLogger\Helper\Kml;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class Track {

  private Session $session;
  private Config $config;
  /** @var Mapper\Position */
  private Mapper\Position $mapperPosition;
  /** @var Mapper\Track */
  private Mapper\Track $mapperTrack;
  private MapperFactory $mapperFactory;

  /**
   * @param MapperFactory $mapperFactory
   * @param Session $session
   * @param Config $config
   */
  public function __construct(Mapper\MapperFactory $mapperFactory, Session $session, Entity\Config $config) {
    $this->session = $session;
    $this->config = $config;
    $this->mapperTrack = $mapperFactory->getMapper(Mapper\Track::class);
    $this->mapperPosition = $mapperFactory->getMapper(Mapper\Position::class);
    $this->mapperFactory = $mapperFactory;
  }

  /**
   * GET /api/tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function get(int $trackId): Response {
    try {
      $track = $this->mapperTrack->fetch($trackId);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    $result = [
      "id" => $track->id,
      "name" => $track->name,
      "userId" => $track->userId,
      "comment" => $track->comment
    ];
    return Response::success($result);
  }

  /**
   * PUT /api/tracks/{id} (update track metadata; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @param Entity\Track $track
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/tracks/{trackId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function update(int $trackId, Entity\Track $track): Response {
    if ($trackId !== $track->id) {
      return Response::unprocessableError("Wrong track id");
    }
    try {
      $this->mapperTrack->update($track);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    }
    return Response::success();
  }

  /**
   * DELETE /api/tracks/{id} (delete track; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
   * @param int $trackId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/tracks/{trackId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function delete(int $trackId): Response {

    try {
      $track = $this->mapperTrack->fetch($trackId);
      $this->mapperPosition->deleteAll($track->userId, $track->id);
      $this->mapperTrack->delete($track);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success();
  }

  /**
   * POST /api/tracks/import (import uploaded file; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @param FileUpload $gpxUpload
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/tracks/import', [ Session::ACCESS_ALL => [ Session::ALLOW_AUTHORIZED ] ])]
  public function import(FileUpload $gpxUpload): Response {
    $gpxFile = $gpxUpload->getTmpName();
    $gpxName = basename($gpxUpload->getName());
    $gpx = new Gpx($gpxName, $this->config, $this->mapperFactory);
    try {
      $result = $gpx->import($this->session->user->id, $gpxFile);
      return Response::success($result);
    } catch (GpxParseException $e) {
      return Response::unprocessableError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    } finally {
      unlink($gpxFile);
    }
  }

  /**
   * GET /api/tracks/{trackId}/export?format={format} (download exported file; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $trackId
   * @param string $format
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}/export', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ],
    ]
  )]
  public function export(int $trackId, string $format): Response {

    try {
      $track = $this->mapperTrack->fetch($trackId);
      $positions = $this->mapperPosition->findAll($trackId);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    }
    if (empty($positions)) {
      return Response::notFound();
    }

    switch ($format) {
      case 'gpx':
        $file = new Gpx($track->name, $this->config);
        break;

      case 'kml':
        $file = new Kml($track->name, $this->config);
        break;

      default:
        return Response::unprocessableError("Unsupported format: $format");
    }

    return Response::file($file->export($positions), $file->getExportedName(), $file->getMimeType());
  }

}
