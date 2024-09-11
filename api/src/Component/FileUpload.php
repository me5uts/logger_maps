<?php
declare(strict_types = 1);

namespace uLogger\Component;

use Exception;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Utils;

class FileUpload {
  public const SELF_UPLOADED_PREFIX = 'self_uploaded';
  private string $name;
  private string $fullPath;
  private string $type;
  private string $tmpName;
  private int $error;
  private int $size;

  public function __construct(array $fileMeta) {
    $this->name = $fileMeta['name'];
    $this->fullPath = $fileMeta['full_path'];
    $this->type = $fileMeta['type'];
    $this->tmpName = $fileMeta['tmp_name'];
    $this->error = $fileMeta['error'];
    $this->size = $fileMeta['size'];
  }

  public static function fromBuffer(string $buffer, string $name, string $type): self {
    $tempFile = tempnam(sys_get_temp_dir(), FileUpload::SELF_UPLOADED_PREFIX);
    file_put_contents($tempFile, $buffer);

    $fileMeta = [
      'name' => $name,
      'full_path' => '',
      'type' => $type,
      'tmp_name' => $tempFile,
      'error' => 0,
      'size' => strlen($buffer)
    ];
    return new self($fileMeta);
  }

  public function getName(): string {
    return $this->name;
  }

  public function getFullPath(): string {
    return $this->fullPath;
  }

  public function getType(): string {
    return $this->type;
  }

  public function getTmpName(): string {
    return $this->tmpName;
  }

  public function getError(): int {
    return $this->error;
  }

  public function getSize(): int {
    return $this->size;
  }

  private static array $mimeMap = [];

  /**
   * @return string[] Mime to extension mapping
   */
  private static function getMimeMap(): array {
    if (empty(self::$mimeMap)) {
      self::$mimeMap['image/jpeg'] = 'jpg';
      self::$mimeMap['image/jpg'] = 'jpg';
      self::$mimeMap['image/x-ms-bmp'] = 'bmp';
      self::$mimeMap['image/gif'] = 'gif';
      self::$mimeMap['image/png'] = 'png';
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
   * @param string $mime
   * @return string|null Extension or null if not found
   */
  private static function getExtension(string $mime): ?string {
    if (self::isKnownMime($mime)) {
      return self::getMimeMap()[$mime];
    }
    return null;
  }

  private static function getMime(string $extension): ?string {
    foreach(self::getMimeMap() as $mime => $ext) {
      if ($extension === $ext) {
        return $mime;
      }
    }
    return null;
  }

  public static function getUploadedPath(string $fileName): string {
    return Utils::getUploadDir() . "/$fileName";
  }

  /**
   * @throws NotFoundException
   */
  public static function getUploadedFile(string $fileName): string {
    $fileContent = file_get_contents(self::getUploadedPath($fileName));

    if ($fileContent === false) {
      throw new NotFoundException();
    } else {
      return $fileContent;
    }
  }

  public static function getMimeType(string $fileName): ?string {
    $pathInfo = pathinfo($fileName);
    return self::getMime($pathInfo['extension']);
  }

  /**
   * Save file to uploads folder, basic sanitizing
   * @param int $trackId
   * @return string Unique file name
   * @throws ServerException|InvalidInputException
   */
  public function add(int $trackId): string {
    try {
      $this->sanitizeUpload();
      return $this->moveFile($trackId);
    } catch (Exception $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw $e;
    }
  }

  /**
   * Delete upload from database and filesystem
   * @param string $path File relative path
   * @return bool False if file exists but can't be unlinked
   */
  public static function delete(string $path): bool {
    $ret = true;
    $filePattern = '/[a-z0-9_.]{20,}/';
    if (preg_match($filePattern, $path)) {
      $path = Utils::getUploadDir() . "/$path";
      if (file_exists($path)) {
        $ret = unlink($path);
      }
    }
    return $ret;
  }

  /**
   * @param bool $checkMime Check with known mime types
   * @return void File metadata
   * @throws InvalidInputException
   */
  public function sanitizeUpload(bool $checkMime = true): void {
    if (!isset($this->name, $this->type, $this->size, $this->tmpName)) {
      $message = 'no uploaded file';
      $lastErr = error_get_last();
      if (!empty($lastErr)) {
        $message = $lastErr['message'];
      }
      throw new InvalidInputException($message);
    }

    $uploadErrors = [];
    $uploadErrors[UPLOAD_ERR_INI_SIZE] = 'Uploaded file exceeds the upload_max_filesize directive in php.ini';
    $uploadErrors[UPLOAD_ERR_FORM_SIZE] = 'Uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
    $uploadErrors[UPLOAD_ERR_PARTIAL] = 'File was only partially uploaded';
    $uploadErrors[UPLOAD_ERR_NO_FILE] = 'No file was uploaded';
    $uploadErrors[UPLOAD_ERR_NO_TMP_DIR] = 'Missing a temporary folder';
    $uploadErrors[UPLOAD_ERR_CANT_WRITE] = 'Failed to write file to disk';
    $uploadErrors[UPLOAD_ERR_EXTENSION] = 'A PHP extension stopped file upload';

    $fileError = $this->error ?? UPLOAD_ERR_OK;
    if ($fileError === UPLOAD_ERR_OK && $this->size > Utils::getSystemUploadLimit()) {
      $fileError = UPLOAD_ERR_FORM_SIZE;
    }
    if ($fileError === UPLOAD_ERR_OK) {
      $file = $this->tmpName;
    } else {
      $message = $uploadErrors[$fileError] ?? 'Unknown error';
      $message .= " ($fileError)";
      throw new InvalidInputException($message);
    }

    if (!$file || !file_exists($file)) {
      throw new InvalidInputException('File not found');
    }
    if ($checkMime && !self::isKnownMime($this->type)) {
      throw new InvalidInputException('Unsupported mime type');
    }
  }

  /**
   * @param int $trackId
   * @return string New file path
   * @throws ServerException
   */
  private function moveFile(int $trackId): string {

    $extension = self::getExtension($this->type);

    do {
      /** @noinspection NonSecureUniqidUsageInspection */
      $fileName = uniqid("{$trackId}_") . ".$extension";
      $destinationPath = Utils::getUploadDir() . "/$fileName";
    } while (file_exists($destinationPath));
    if (is_uploaded_file($this->tmpName) && !move_uploaded_file($this->tmpName, $destinationPath)) {
      throw new ServerException('Move uploaded file failed');
    } elseif ($this->isSelfUploaded($this->tmpName) && !rename($this->tmpName, $destinationPath)) {
      throw new ServerException('Move self uploaded file failed');
    }
    return $fileName;
  }

  private function isSelfUploaded(string $tmpName): bool {
    $tempDir = realpath(sys_get_temp_dir());
    $realTmpName = realpath($tmpName);
    if (dirname($realTmpName) !== $tempDir) {
      return false;
    }

    $fileName = basename($realTmpName);
    if (!str_starts_with($fileName, self::SELF_UPLOADED_PREFIX)) {
      return false;
    }

    return true;
  }

}


