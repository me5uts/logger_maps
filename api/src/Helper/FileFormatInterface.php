<?php

namespace uLogger\Helper;

interface FileFormatInterface {

  public function import(int $userId, string $filePath): array;
  public function export(array $positions): string;
  public function getExportedName(): string;
  public function getMimeType(): string;
}
