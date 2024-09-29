<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use uLogger\Exception\NotFoundException;
use uLogger\Helper\Utils;

class File extends AbstractEntity
{
  private string $fileName;
  private string $content;
  private string $path;
  private string $mimeType;
  private static array $mimeMap = [];

  /**
   * @throws NotFoundException
   */
  public static function createFromUpload(string $uploadedFileName): self {
    $file = new self();
    $file->setFromUpload($uploadedFileName);
    return $file;
  }

  public function getFileName(): string {
    return $this->fileName;
  }

  public function setFileName(string $fileName): void {
    $this->fileName = $fileName;
  }
  public function getContent(): string {
    return $this->content;
  }

  public function setContent(string $content): void {
    $this->content = $content;
  }

  public function getMimeType(): ?string {
    return $this->mimeType;
  }

  public function setMimeType(string $mimeType): void {
    $this->mimeType = $mimeType;
  }

  public function getPath(): ?string {
    return $this->path;
  }

  public function setPath(string $path): void {
    $this->path = $path;
  }

  /**
   * @throws NotFoundException
   */
  private function setFromUpload(string $fileName): void {
    $this->fileName = $fileName;
    $fileContent = file_get_contents($this->getUploadedPath());

    if ($fileContent === false) {
      throw new NotFoundException();
    }

    $this->content = $fileContent;
    $extension = pathinfo($this->fileName, PATHINFO_EXTENSION);
    $this->mimeType = self::getMime($extension);
    $this->path = $this->getUploadedPath();
  }

  private function getUploadedPath(): string {
    return Utils::getUploadDir() . "/$this->fileName";
  }


  private static function getMime(string $extension): ?string {
    foreach(self::getMimeMap() as $mime => $ext) {
      if ($extension === $ext) {
        return $mime;
      }
    }
    return null;
  }

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
  public static function isKnownMime(string $mime): bool {
    return array_key_exists($mime, self::getMimeMap());
  }

  /**
   * Get file extension for given mime
   * @param string $mime
   * @return string|null Extension or null if not found
   */
  public static function getExtension(string $mime): ?string {
    if (self::isKnownMime($mime)) {
      return self::getMimeMap()[$mime];
    }
    return null;
  }

  /**
   * Delete file from filesystem
   * @return bool False if file exists but can't be unlinked
   */
  public function delete(): bool {
    $ret = true;
    $filePattern = '/[a-z0-9_.]{20,}/';
    if (preg_match($filePattern, $this->fileName)) {
      $path = Utils::getUploadDir() . "/$this->fileName";
      if (file_exists($path)) {
        $ret = unlink($path);
      }
    }
    return $ret;
  }
}
