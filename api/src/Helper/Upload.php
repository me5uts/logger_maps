<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Helper;

use ErrorException;
use Exception;

/**
 * Uploaded files
 */
class Upload {

  private const META_TYPE = "type";
  public const META_NAME = "name";
  public const META_TMP_NAME = "tmp_name";
  private const META_ERROR = "error";
  private const META_SIZE = "size";
  private const UPLOAD_DIR = "uploads";
  private static $filePattern = "/[a-z0-9_.]{20,}/";
  private static $mimeMap = [];

  /**
   * @return string[] Mime to extension mapping
   */
  private static function getMimeMap(): array {
    if (empty(self::$mimeMap)) {
      self::$mimeMap["image/jpeg"] = "jpg";
      self::$mimeMap["image/jpg"] = "jpg";
      self::$mimeMap["image/x-ms-bmp"] = "bmp";
      self::$mimeMap["image/gif"] = "gif";
      self::$mimeMap["image/png"] = "png";
    }
    return self::$mimeMap;
  }

  /**
   * Is mime accepted type
   * @param string $mime Mime type
   * @return bool True if known
   */
  private static function isKnownMime(string $mime): bool {
    return array_key_exists($mime, self::getMimeMap());
  }

  /**
   * Get file extension for given mime
   * @param $mime
   * @return string|null Extension or null if not found
   */
  private static function getExtension($mime): ?string {
    if (self::isKnownMime($mime)) {
      return self::getMimeMap()[$mime];
    }
    return null;
  }

  /**
   * Save file to uploads folder, basic sanitizing
   * @param array $uploaded File meta array from $_FILES[]
   * @param int $trackId
   * @return string|null Unique file name, null on error
   */
  public static function add(array $uploaded, int $trackId): ?string {
    try {
      $fileMeta = self::sanitizeUpload($uploaded);
    } catch (Exception $e) {
      syslog(LOG_ERR, $e->getMessage());
      // save exception to txt file as image replacement?
      return null;
    }

    $extension = self::getExtension($fileMeta[self::META_TYPE]);

    do {
      /** @noinspection NonSecureUniqidUsageInspection */
      $fileName = uniqid("{$trackId}_") . ".$extension";
    } while (file_exists(Utils::getUploadDir() . "/$fileName"));
    if (move_uploaded_file($fileMeta[self::META_TMP_NAME], Utils::getUploadDir() . "/$fileName")) {
      return $fileName;
    }
    return null;
  }

  /**
   * Delete upload from database and filesystem
   * @param string $path File relative path
   * @return bool False if file exists but can't be unlinked
   */
  public static function delete(string $path): bool {
    $ret = true;
    if (preg_match(self::$filePattern, $path)) {
      $path = Utils::getUploadDir() . "/$path";
      if (file_exists($path)) {
        $ret = unlink($path);
      }
    }
    return $ret;
  }

  /**
   * @param array $fileMeta File meta array from $_FILES[]
   * @param boolean $checkMime Check with known mime types
   * @return array File metadata array
   * @throws ErrorException Internal server exception
   * @throws Exception File upload exception
   */
  public static function sanitizeUpload(array $fileMeta, bool $checkMime = true): array {
    if (!isset($fileMeta[self::META_NAME], $fileMeta[self::META_TYPE], $fileMeta[self::META_SIZE], $fileMeta[self::META_TMP_NAME])) {
      $message = "no uploaded file";
      $lastErr = error_get_last();
      if (!empty($lastErr)) {
        $message = $lastErr["message"];
      }
      throw new ErrorException($message);
    }

    $uploadErrors = [];
    $uploadErrors[UPLOAD_ERR_INI_SIZE] = "Uploaded file exceeds the upload_max_filesize directive in php.ini";
    $uploadErrors[UPLOAD_ERR_FORM_SIZE] = "Uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
    $uploadErrors[UPLOAD_ERR_PARTIAL] = "File was only partially uploaded";
    $uploadErrors[UPLOAD_ERR_NO_FILE] = "No file was uploaded";
    $uploadErrors[UPLOAD_ERR_NO_TMP_DIR] = "Missing a temporary folder";
    $uploadErrors[UPLOAD_ERR_CANT_WRITE] = "Failed to write file to disk";
    $uploadErrors[UPLOAD_ERR_EXTENSION] = "A PHP extension stopped file upload";

    $file = null;
    $fileError = $fileMeta[self::META_ERROR] ?? UPLOAD_ERR_OK;
    if ($fileError === UPLOAD_ERR_OK && $fileMeta[self::META_SIZE] > Utils::getSystemUploadLimit()) {
      $fileError = UPLOAD_ERR_FORM_SIZE;
    }
    if ($fileError === UPLOAD_ERR_OK) {
      $file = $fileMeta[self::META_TMP_NAME];
    } else {
      $message = $uploadErrors[$fileError] ?? "Unknown error";
      $message .= " ($fileError)";
      throw new Exception($message);
    }

    if (!$file || !file_exists($file)) {
      throw new ErrorException("File not found");
    }
    if ($checkMime && !self::isKnownMime($fileMeta[self::META_TYPE])) {
      throw new Exception("Unsupported mime type");
    }
    return $fileMeta;
  }
}
