<?php
declare(strict_types = 1);
/*
 * μlogger
 *
 * Copyright(C) 2021 Bartek Fabiszewski (www.fabiszewski.net)
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

namespace uLogger\Tests\lib;

use Error;
use PHPUnit\Framework\TestCase;
use TypeError;

trait AssertExceptionTrait {

  public static function assertError(string $expectedException, callable $test, ?string $message = null): void {
    $messagePrefix = $message ? "$message: " : "";
    try {
      $test();
    } catch (Error $actualException) {
      TestCase::assertInstanceOf($expectedException, $actualException, "{$messagePrefix}Unexpected exception thrown");
      return;
    }

    TestCase::fail("{$messagePrefix}No exception thrown");
  }

  public static function assertTypeError(callable $test, ?string $message = null): void {
    self::assertError(TypeError::class, $test, $message);
  }
}

?>
