<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Auth;
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

class Track {

  private Auth $auth;
  private Config $config;

  /**
   * @param Auth $auth
   * @param Config $config
   */
  public function __construct(Auth $auth, Config $config) {
    $this->auth = $auth;
    $this->config = $config;
  }
  /**
   * GET /api/tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
  ])]
  public function get(int $trackId): Response {
    $track = new Entity\Track($trackId);
    if (!$track->isValid) {
      return Response::notFound();
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
  #[Route(Request::METHOD_PUT, '/api/tracks/{trackId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function update(int $trackId, Entity\Track $track): Response {
    if ($trackId !== $track->id) {
      return Response::unprocessableError("Wrong track id");
    }
    try {
      $track->update();
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
  #[Route(Request::METHOD_DELETE, '/api/tracks/{trackId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function delete(int $trackId): Response {

    $track = new Entity\Track($trackId);
    if (!$track->isValid) {
      return Response::notFound();
    }

    if ($track->delete() === false) {
      return Response::internalServerError("Track delete failed");
    }

    return Response::success();
  }

  /**
   * POST /api/tracks/import (import uploaded file; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @param FileUpload $gpxUpload
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/tracks/import', [ Auth::ACCESS_ALL => [ Auth::ALLOW_AUTHORIZED ] ])]
  public function import(FileUpload $gpxUpload): Response {
    $gpxFile = $gpxUpload->getTmpName();
    $gpxName = basename($gpxUpload->getName());
    $gpx = new Gpx($gpxName, $this->config);
    try {
      $result = $gpx->import($this->auth->user->id, $gpxFile);
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
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ],
    ]
  )]
  public function export(int $trackId, string $format): Response {
    $track = new Entity\Track($trackId);
    if (!$track->isValid) {
      return Response::notFound();
    }
    $positions = Entity\Position::getAll(null, $trackId);
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
