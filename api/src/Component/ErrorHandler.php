<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

use Exception;

class ErrorHandler {

  public static function init(): void {
    error_reporting(E_ERROR | E_COMPILE_ERROR | E_WARNING);
    ini_set('display_errors', '0');

    set_error_handler('uLogger\Component\ErrorHandler::errorHandler');
    set_exception_handler('uLogger\Component\ErrorHandler::exceptionHandler');
    register_shutdown_function('uLogger\Component\ErrorHandler::fatalErrorHandler');
  }

  public static function errorHandler(int $errno, string $error, string $file, int $line): bool {
    if (!(error_reporting() & $errno)) {
      return false;
    }
    self::sendErrorAndExit("Error: $error [$file:$line]", $errno);
  }

  /**
   * @param Exception $exception
   * @return no-return
   */
  public static function exceptionHandler(Exception $exception): void {
    self::sendErrorAndExit("Exception: $exception", $exception->getCode());
  }

  public static function fatalErrorHandler(): void {
    $error = error_get_last();
    $errors = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR];
    // only exit on fatal error type
    if ($error && in_array($error['type'], $errors, true) && error_reporting()) {
      self::sendErrorAndExit("Fatal error: {$error['message']} [{$error['file']}:{$error['line']}]", E_CORE_ERROR);
    }
  }

  /**
   * @param string $message
   * @param int $errno
   * @return no-return
   */
  private static function sendErrorAndExit(string $message, int $errno): void {

    http_response_code(Response::CODE_5_INTERNAL);
    Response::sendHeader(Request::CONTENT_TYPE, Response::TYPE_JSON);

    die(json_encode([
      'error' => true,
      'message' => "$message ($errno)",
    ]));
  }
}
