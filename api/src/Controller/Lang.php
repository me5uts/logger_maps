<?php
declare(strict_types = 1);
/* μlogger
 *
 * Copyright(C) 2019 Bartek Fabiszewski (www.fabiszewski.net)
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

namespace uLogger\Controller;

use uLogger\Helper\Utils;

/**
 * Localization
 */
class Lang {

  /**
   * Available languages
   *
   * @var array
   */
  private static $languages = [
    "ca" => "Català",
    "cs" => "Čeština",
    "de" => "Deutsch",
    "el" => "Ελληνικά",
    "en" => "English",
    "es" => "Español",
    "eu" => "Euskera",
    "fi" => "Suomi",
    "fr" => "Français",
    "it" => "Italiano",
    "pl" => "Polski",
    "pt-br" => "Português (Br)",
    "ru" => "Русский",
    "sk" => "Slovenčina"
  ];

  /**
   * Application strings
   * Array of key => translation pairs
   *
   * @var array
   */
  private $strings;
  /**
   * Setup script strings
   * Array of key => translation pairs
   *
   * @var array
   */
  private $setupStrings;

  /**
   * Constructor
   *
   * @param Config $config Config
   */
  public function __construct(Config $config) {
    $language = $config->lang;
    $lang = [];
    $langSetup = [];
    // always load en base
    require(Utils::getSourceDir() . "/Lang/en.php");

    // override with translated strings if needed
    // missing strings will be displayed in English
    if ($language !== "en" && array_key_exists($language, self::$languages)) {
      require(Utils::getSourceDir() . "/Lang/$language.php");
    }

    $this->strings = $lang;
    $this->setupStrings = $langSetup;
  }

  /**
   * Get supported languages array
   * Language code => Native language name
   *
   * @return array
   */
  public static function getLanguages(): array {
    return self::$languages;
  }

  /**
   * Get translated strings array
   * Key => translation string
   *
   * @return array
   */
  public function getStrings(): array {
    return $this->strings;
  }

  /**
   * Get translated strings array for setup script
   * Key => translation string
   *
   * @return array
   */
  public function getSetupStrings(): array {
    return $this->setupStrings;
  }

}

?>
