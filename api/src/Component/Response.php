<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

use Exception;
use JsonException;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\GpxParseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;

class Response {

  public const TYPE_JSON = 'application/json';
  public const TYPE_GPX = 'application/gpx+xml';
  public const TYPE_KML = 'application/vnd.google-earth.kml+xml';

  public const CODE_1_CONTINUE = 100;

  public const CODE_2_OK = 200;
  public const CODE_2_CREATED = 201;
  public const CODE_2_NOCONTENT = 204;

  public const CODE_3_FOUND = 302;

  public const CODE_4_UNAUTHORIZED = 401;
  public const CODE_4_FORBIDDEN = 403;
  public const CODE_4_NOTFOUND = 404;
  public const CODE_4_CONFLICT = 409;
  public const CODE_4_UNPROCESSABLE = 422;

  public const CODE_5_INTERNAL = 500;
  public const CODE_5_UNAVAILABLE = 503;

  /** @var int  */
  private int $code;
  /** @var array|object|string|null  */
  private mixed $payload;
  /** @var string|null */
  private ?string $contentType;
  /** @var array  */
  private array $extraHeaders = [];

  public function __construct($payload = null, int $code = self::CODE_2_OK, $contentType = self::TYPE_JSON) {
    $this->payload = $payload;
    $this->code = $code;
    $this->contentType = $contentType;
  }


  /**
   * @return int
   */
  public function getCode(): int {
    return $this->code;
  }

  /**
   * @return object|array|string|null
   */
  public function getPayload(): object|array|string|null {
    return $this->payload;
  }

  /**
   * @return string
   */
  public function getContentType(): string {
    return $this->contentType;
  }

  public function getExtraHeaders(): array {
    return $this->extraHeaders;
  }

  private function sendHttpCodeHeader(): void {
    $codeTexts = [
      self::CODE_2_OK => 'OK',
      self::CODE_2_CREATED => 'Created',
      self::CODE_2_NOCONTENT => 'No Content',
      self::CODE_3_FOUND => 'Found',
      self::CODE_4_UNAUTHORIZED => 'Unauthorized',
      self::CODE_4_FORBIDDEN => 'Forbidden',
      self::CODE_4_NOTFOUND => 'Not Found',
      self::CODE_4_CONFLICT => 'Conflict',
      self::CODE_4_UNPROCESSABLE => 'Unprocessable entity',
      self::CODE_5_INTERNAL => 'Internal server error',
      self::CODE_5_UNAVAILABLE => 'Service unavailable',
    ];

    $text = $codeTexts[$this->code] ?? '';

    header("HTTP/1.1 $this->code $text", true, $this->code);
  }

  public function setContentType(string $contentType): void {
    $this->contentType = $contentType;
  }

  private function setErrorBody(string $message): void {
    $this->payload = [];
    $this->payload['error'] = true;
    $this->payload['message'] = $message;
  }

  public static function error(string $message, int $code): Response {
    $response = new Response(null, $code);
    $response->setErrorBody($message);
    return $response;
  }

  public static function fieldsError(array $payload, int $code): Response {
    return new Response([ 'fields' => $payload ], $code);
  }

  public static function success($payload = null, int $code = self::CODE_2_OK): Response {
    if ($payload === null) {
      $contentType = null;
      $code = self::CODE_2_NOCONTENT;
    } else {
      $contentType = self::TYPE_JSON;
    }
    return new Response($payload, $code, $contentType);
  }

  public static function fileAttachment(string $fileContent, string $filename, string $contentType): Response {
    $response = self::file($fileContent, $contentType);
    $response->extraHeaders[Request::CONTENT_DISPOSITION] = "attachment; filename=\"$filename\"";
    return $response;
  }

  public static function file(string $fileContent, string $contentType): Response {
    return new Response($fileContent, self::CODE_2_OK, $contentType);
  }

  public static function internalServerError(string $message): Response {
    return self::error($message, self::CODE_5_INTERNAL);
  }

  public static function databaseError(string $message = 'Database error'): Response {
    return self::internalServerError($message);
  }

  public static function unprocessableError(string $message = 'Unprocessable data error'): Response {
    return self::error($message, self::CODE_4_UNPROCESSABLE);
  }

  public static function conflictError(string $message): Response {
    return self::error($message, self::CODE_4_CONFLICT);
  }

  public static function created($message = null): Response {
    return self::success($message, self::CODE_2_CREATED);
  }

  public static function notFound(): Response {
    return new Response(null, self::CODE_4_NOTFOUND);
  }

  public static function redirect($path): Response {
    $response = new Response(null, self::CODE_3_FOUND);
    $response->extraHeaders["Location"] = "$path";
    $response->setContentType('');
    return $response;
  }

  public static function notAuthorized(): Response {
    return new Response(null, self::CODE_4_UNAUTHORIZED);
  }

  public static function continue(): Response {
    return new Response(null, self::CODE_1_CONTINUE);
  }

  public static function forbidden(string $message = null): Response {
    $response = new Response(null, self::CODE_4_FORBIDDEN);
    if ($message) {
      $response->setErrorBody($message);
    }
    return $response;
  }

  public static function exception(Exception $e): Response {
    if ($e instanceof DatabaseException) {
      return Response::databaseError($e->getMessage());
    } elseif ($e instanceof ServerException) {
      return Response::internalServerError($e->getMessage());
    } elseif ($e instanceof InvalidInputException || $e instanceof GpxParseException) {
      return Response::unprocessableError($e->getMessage());
    } elseif ($e instanceof NotFoundException) {
      return Response::notFound();
    }
    return Response::internalServerError("An unexpected error occurred.");
  }

  public function send(): void {
    foreach ($this->extraHeaders as $key => $value) {
      self::sendHeader($key, $value);
    }
    $this->sendHttpCodeHeader();
    $responseBody = '';
    if ($this->payload !== null) {
      if ($this->contentType === self::TYPE_JSON) {
        try {
          $responseBody = json_encode($this->payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
          $response = self::error($e->getMessage(), self::CODE_5_INTERNAL);
          $response->sendAndExit();
        }
      } else {
        $responseBody = $this->payload;
      }
      if (!empty($this->contentType)) {
        self::sendHeader(Request::CONTENT_TYPE, $this->contentType);
      }
    }

    self::sendHeader(Request::CONTENT_LENGTH,  strlen($responseBody));
    echo $responseBody;
  }

  public static function sendHeader(string $key, mixed $value): void {
    header("$key: $value");
  }

  /**
   * @return no-return
   */
  public function sendAndExit(): void {
    $this->send();
    exit();
  }

}
