<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use Exception;
use uLogger\Attribute\Route;
use uLogger\Component\FileUpload;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Exception\NotFoundException;
use uLogger\Mapper;

class Position extends AbstractController {

  /**
   * Get positions for track, optionally filter by minimum ID
   * GET /api/tracks/{id}/positions[?afterId={afterId}] (track positions with optional filter; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @param int|null $afterId
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}/positions', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function getAll(int $trackId, ?int $afterId = null): Response {
    try {
      $positions = $this->mapper(Mapper\Position::class)->findAll($trackId, $afterId);

      if (!empty($positions)) {
        if ($afterId) {
          try {
            $prevPosition = $this->mapper(Mapper\Position::class)->fetch($afterId);
          } catch (NotFoundException) {/* ignored */}

        }
        foreach ($positions as $position) {
          $position->meters = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
          $position->seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
          $prevPosition = $position;
        }
      }
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }
    return Response::success($positions);
  }

  /**
   * PUT /api/positions/{id} (update position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param Entity\Position $position
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_PUT, '/api/positions/{positionId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function update(int $positionId, Entity\Position $position): Response {

    if ($positionId !== $position->id) {
      return Response::unprocessableError("Wrong position id");
    }

    try {
      $currentPosition = $this->mapper(Mapper\Position::class)->fetch($positionId);
      $currentPosition->comment = $position->comment;
      $this->mapper(Mapper\Position::class)->update($currentPosition);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success();
  }

  /**
   * DELETE /api/positions/{id} (delete position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function delete(int $positionId): Response {
    try {
      $position = $this->mapper(Mapper\Position::class)->fetch($positionId);
      $this->mapper(Mapper\Position::class)->delete($position);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success();
  }

  /**
   * POST /api/positions/{id}/image (add image to position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param FileUpload $imageUpload
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_POST, '/api/positions/{positionId}/image', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function addImage(int $positionId, FileUpload $imageUpload): Response {

    try {
      $position = $this->mapper(Mapper\Position::class)->fetch($positionId);
      $this->mapper(Mapper\Position::class)->setImage($position, $imageUpload);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success([ "image" => $position->image ]);
  }

  /**
   * DELETE /api/positions/{id}/image (delete image from position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}/image', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function deleteImage(int $positionId): Response {

    try {
      $position = $this->mapper(Mapper\Position::class)->fetch($positionId);
      $this->mapper(Mapper\Position::class)->removeImage($position);
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }

    return Response::success();
  }
}
