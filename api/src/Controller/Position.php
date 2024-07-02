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
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class Position {
  /** @var Mapper\Position */
  private Mapper\Position $mapper;

  /**
   * @param MapperFactory $mapperFactory
   */
  public function __construct(Mapper\MapperFactory $mapperFactory) {
    $this->mapper = $mapperFactory->getMapper(Mapper\Position::class);
  }

  /**
   * Get positions for track, optionally filter by minimum ID
   * GET /api/tracks/{id}/positions[?afterId={afterId}] (track positions with optional filter; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
   * @param int $trackId
   * @param int|null $afterId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/tracks/{trackId}/positions', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function getAll(int $trackId, ?int $afterId = null): Response {
    try {
      $positions = $this->mapper->findAll($trackId, $afterId);

      if (!empty($positions)) {
        if ($afterId) {
          try {
            $prevPosition = $this->mapper->fetch($afterId);
          } catch (NotFoundException) {/* ignored */}

        }
        foreach ($positions as $position) {
          $position->meters = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
          $position->seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
          $prevPosition = $position;
        }
      }
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }
    return Response::success($positions);
  }

  /**
   * PUT /api/positions/{id} (update position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param Entity\Position $position
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/positions/{positionId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function update(int $positionId, Entity\Position $position): Response {

    if ($positionId !== $position->id) {
      return Response::unprocessableError("Wrong position id");
    }

    try {
      $currentPosition = $this->mapper->fetch($positionId);
      $currentPosition->comment = $position->comment;
      $this->mapper->update($currentPosition);
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
   * DELETE /api/positions/{id} (delete position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function delete(int $positionId): Response {
    try {
      $position = $this->mapper->fetch($positionId);
      $this->mapper->delete($position);
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
   * POST /api/positions/{id}/image (add image to position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @param FileUpload $imageUpload
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/positions/{positionId}/image', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function addImage(int $positionId, FileUpload $imageUpload): Response {

    try {
      $position = $this->mapper->fetch($positionId);
      $this->mapper->setImage($position, $imageUpload);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    }

    return Response::success([ "image" => $position->image ]);
  }

  /**
   * DELETE /api/positions/{id}/image (delete image from position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
   * @param int $positionId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/positions/{positionId}/image', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ] ])]
  public function deleteImage(int $positionId): Response {

    try {
      $position = $this->mapper->fetch($positionId);
      $this->mapper->removeImage($position);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success();
  }
}
