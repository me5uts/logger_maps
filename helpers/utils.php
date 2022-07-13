<?php
declare(strict_types = 1);
/* μlogger
 *
 * Copyright(C) 2017 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Various util functions
 */
class uUtils {

  /**
   * Calculate maximum allowed size of uploaded file
   * for current PHP settings
   *
   * @return int Number of bytes
   */
  public static function getSystemUploadLimit(): int {
    $upload_max_filesize = self::iniGetBytes('upload_max_filesize');
    $post_max_size = self::iniGetBytes('post_max_size');
    // post_max_size = 0 means unlimited size
    if ($post_max_size === 0) { $post_max_size = $upload_max_filesize; }
    $memory_limit = self::iniGetBytes('memory_limit');
    // memory_limit = -1 means no limit
    if ($memory_limit < 0) { $memory_limit = $post_max_size; }
    return min($upload_max_filesize, $post_max_size, $memory_limit);
  }

  /**
   * @param $path string Path
   * @return bool True if is absolute
   */
  public static function isAbsolutePath(string $path): bool {
    return $path[0] === '/' || $path[0] === '\\' || preg_match('/^[a-zA-Z]:\\\\/', $path);
  }

  /**
   * Get number of bytes from ini parameter.
   * Optionally parses shorthand byte values (G, M, B)
   *
   * @param string $iniParam Ini parameter name
   * @return int Bytes
   * @noinspection PhpMissingBreakStatementInspection
   */
  private static function iniGetBytes(string $iniParam): int {
    $iniStr = ini_get($iniParam);
    $val = (float) $iniStr;
    $suffix = substr(trim($iniStr), -1);
    if (ctype_alpha($suffix)) {
      switch (strtolower($suffix)) {
        case 'g':
          $val *= 1024;
        case 'm':
          $val *= 1024;
        case 'k':
          $val *= 1024;
      }
    }
    return (int) $val;
  }

  /**
   * Exit with error message
   *
   * @param string $errorMessage Message
   * @param array|null $extra Optional array of extra parameters
   */
  public static function exitWithError(string $errorMessage, ?array $extra = null): void {
    $extra['message'] = $errorMessage;
    self::exitWithStatus(true, $extra);
  }

  /**
   * Exit with successful status code
   *
   * @param array|null $extra Optional array of extra parameters
   */
  public static function exitWithSuccess(?array $extra = null): void {
    self::exitWithStatus(false, $extra);
  }

  /**
   * Exit with xml response
   * @param boolean $isError Error if true
   * @param array|null $extra Optional array of extra parameters
   */
  private static function exitWithStatus(bool $isError, ?array $extra = null): void {
    $output = [];
    if ($isError) {
      $output["error"] = true;
    }
    if (!empty($extra)) {
      foreach ($extra as $key => $value) {
        $output[$key] = $value;
      }
    }
    header("Content-type: application/json");
    echo json_encode($output);
    exit;
  }

  /**
   * Calculate app base URL
   * Returned URL has trailing slash.
   *
   * @return string URL
   */
  public static function getBaseUrl(): string {
    $proto = (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] === "" || $_SERVER["HTTPS"] === "off") ? "http://" : "https://";
    // Check if we are behind an HTTPS proxy
    if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
      $proto = "https://";
    }
    $host = $_SERVER["HTTP_HOST"] ?? "";
    if (realpath($_SERVER["SCRIPT_FILENAME"])) {
      $scriptPath = substr(dirname(realpath($_SERVER["SCRIPT_FILENAME"])), strlen(ROOT_DIR));
    } else {
      // for phpunit
      $scriptPath = substr(dirname($_SERVER["SCRIPT_FILENAME"]), strlen(ROOT_DIR));
    }
    $self = dirname($_SERVER["PHP_SELF"]);
    $path = str_replace("\\", "/", substr($self, 0, strlen($self) - strlen($scriptPath)));

    return $proto . str_replace("//", "/", $host . $path . "/");
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postFloat(string $name, $default = null) {
    return self::requestValue($name, $default, INPUT_POST, FILTER_VALIDATE_FLOAT);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postPass(string $name, $default = null) {
    return self::requestValue($name, $default, INPUT_POST);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postString(string $name, $default = null) {
    return self::requestString($name, $default, INPUT_POST);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function getString(string $name, $default = null) {
    return self::requestString($name, $default, INPUT_GET);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postBool(string $name, $default = null) {
    $input = filter_input(INPUT_POST, $name, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $input !== null ? (bool) $input : $default;
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function getBool(string $name, $default = null) {
    $input = filter_input(INPUT_GET, $name, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $input !== null ? (bool) $input : $default;
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postInt(string $name, $default = null) {
    return self::requestInt($name, $default, INPUT_POST);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function getInt(string $name, $default = null) {
    return self::requestInt($name, $default, INPUT_GET);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function requestFile(string $name, $default = null) {
    if (isset($_FILES[$name])) {
      $files = $_FILES[$name];
      if (isset($files["name"], $files["type"], $files["size"], $files["tmp_name"])) {
        return $_FILES[$name];
      }
    }
    return $default;
  }

  /**
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function postArray(string $name, $default = null) {
    return ((isset($_POST[$name]) && is_array($_POST[$name])) ? $_POST[$name] : $default);
  }

  /**
   * @param string $name Input name
   * @param boolean $checkMime Optionally check mime with known types
   * @return array File metadata array
   * @throws Exception Upload exception
   * @throws ErrorException Internal server exception
   */
  public static function requireFile(string $name, bool $checkMime = false): array {
    return uUpload::sanitizeUpload($_FILES[$name], $checkMime);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @param $type
   * @return mixed|string
   */
  private static function requestString(string $name, $default, $type) {
    if (is_string(($val = self::requestValue($name, $default, $type)))) {
      return trim($val);
    }
    return $val;
  }

  /**
   * @param string $name
   * @param mixed $default
   * @param int $type
   * @return mixed|int
   */
  private static function requestInt(string $name, $default, int $type) {
    if (is_float(($val = self::requestValue($name, $default, $type, FILTER_VALIDATE_FLOAT)))) {
      return (int) round($val);
    }
    return self::requestValue($name, $default, $type, FILTER_VALIDATE_INT);
  }

  /**
   * @param string $name
   * @param mixed $default
   * @param int $type
   * @param int $filters
   * @return mixed
   */
  private static function requestValue(string $name, $default, int $type, int $filters = FILTER_DEFAULT) {
    $input = filter_input($type, $name, $filters);
    if ($input !== false && $input !== null) {
      return $input;
    }
    return $default;
  }

}

?>
