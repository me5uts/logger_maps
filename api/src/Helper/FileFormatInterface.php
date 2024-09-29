<?php
declare(strict_types = 1);

namespace uLogger\Helper;

use uLogger\Entity\File;

interface FileFormatInterface {

  public function import(int $userId, string $filePath): array;
  public function export(array $positions): File;

}
