<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use Exception;
use uLogger\Component\FileUpload;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Helper\Gpx;
use uLogger\Helper\Kml;
use uLogger\Mapper;

class Track extends AbstractController {

  /**
   * GET /api/tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function get(int $trackId): Response {
    try {
      $track = $this->mapper(Mapper\Track::class)->fetch($trackId);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success($track);
  }

  /**
   * PUT /api/tracks/{id} (update track metadata; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @param Entity\Track $track
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_PUT, '/api/tracks/{trackId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function update(int $trackId, Entity\Track $track): Response {
    if ($trackId !== $track->id) {
      return Response::unprocessableError("Wrong track id");
    }
    try {
      $this->mapper(Mapper\Track::class)->update($track);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }
    return Response::success();
  }

  /**
   * DELETE /api/tracks/{id} (delete track; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
   * @param int $trackId
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_DELETE, '/api/tracks/{trackId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function delete(int $trackId): Response {

    try {
      $track = $this->mapper(Mapper\Track::class)->fetch($trackId);
      $this->mapper(Mapper\Position::class)->deleteAll($track->userId, $track->id);
      $this->mapper(Mapper\Track::class)->delete($track);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success();
  }

  /**
   * POST /api/tracks/import (import uploaded file; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @param FileUpload $gpxUpload
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_POST, '/api/tracks/import', [ Session::ACCESS_ALL => [ Session::ALLOW_AUTHORIZED ] ])]
  public function import(FileUpload $gpxUpload): Response {
    $gpxFile = $gpxUpload->getTmpName();
    $gpxName = basename($gpxUpload->getName());
    try {
      $gpx = new Gpx($gpxName, $this->config, $this->mapperFactory);
      $result = $gpx->import($this->session->user->id, $gpxFile);
      return Response::success($result);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    } finally {
      unlink($gpxFile);
    }
  }

  /**
   * GET /api/tracks/{trackId}/export?format={format} (download exported file; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $trackId
   * @param string $format
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}/export', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ],
    ]
  )]
  public function export(int $trackId, string $format): Response {

    try {
      $track = $this->mapper(Mapper\Track::class)->fetch($trackId);
      $positions = $this->mapper(Mapper\Position::class)->findAll($trackId);

      if (empty($positions)) {
        return Response::notFound();
      }

      switch ($format) {
        case 'gpx':
          $file = new Gpx($track->name, $this->config, $this->mapperFactory);
          break;

        case 'kml':
          $file = new Kml($track->name, $this->config);
          break;

        default:
          return Response::unprocessableError("Unsupported format: $format");
      }
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }
    return Response::file($file->export($positions), $file->getExportedName(), $file->getMimeType());
  }

}
