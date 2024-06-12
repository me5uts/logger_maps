<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use ErrorException;
use uLogger\Component\Auth;
use uLogger\Component\FileUpload;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity;
use uLogger\Exception\ServerException;

class Position {

  /**
   * Get positions for track, optionally filter by minimum ID
   * GET /api/tracks/{id}/positions[?afterId={afterId}] (track positions with optional filter; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @param int|null $afterId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}/positions', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
  ])]
  public function getAll(int $trackId, ?int $afterId = null): Response {
    $positions = Entity\Position::getAll(null, $trackId, $afterId);
    $result = [];
    if ($positions === false) {
      $result = [ "error" => true ];
    } elseif (!empty($positions)) {
      if ($afterId) {
        $afterPosition = new Entity\Position($afterId);
        if ($afterPosition->isValid) {
          $prevPosition = $afterPosition;
        }
      }
      foreach ($positions as $position) {
        $meters = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
        $seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
        $result[] = Entity\Position::getArray($position, $meters, $seconds);
        $prevPosition = $position;
      }
    }
    return Response::success($result);
  }

  /**
   * PUT /api/positions/{id} (update position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param Entity\Position $position
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/positions/{positionId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function update(int $positionId, Entity\Position $position): Response {

    if ($positionId !== $position->id) {
      return Response::unprocessableError("Wrong position id");
    }

    $currentPosition = new Entity\Position($positionId);
    if (!$currentPosition->isValid) {
      return Response::notFound();
    }

    $currentPosition->comment = $position->comment;
    if ($currentPosition->update() === false) {
      return Response::internalServerError("Position update failed");
    }

    return Response::success();
  }

  /**
   * DELETE /api/positions/{id} (delete position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function delete(int $positionId): Response {
    $position = new Entity\Position($positionId);
    if (!$position->isValid) {
      return Response::notFound();
    }

    if ($position->delete() === false) {
      return Response::internalServerError("Position delete failed");
    }

    return Response::success();
  }

  /**
   * POST /api/positions/{id}/image (add image to position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param FileUpload $imageUpload
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/positions/{positionId}/image', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function addImage(int $positionId, FileUpload $imageUpload): Response {

    $position = new Entity\Position($positionId);
    if (!$position->isValid) {
      return Response::notFound();
    }

    try {
      if ($position->setImage($imageUpload) === false) {
        return Response::internalServerError("Position image adding failed");
      }
    } catch (ErrorException|ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }
    return Response::success([ "image" => $position->image ]);

  }

  /**
   * DELETE /api/positions/{id}/image (delete image from position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}/image', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ])]
  public function deleteImage(int $positionId): Response {

    $position = new Entity\Position($positionId);
    if (!$position->isValid) {
      return Response::notFound();
    }

    if ($position->removeImage() === false) {
      return Response::internalServerError("Position image delete failed");
    }

    return Response::success();
  }
}
