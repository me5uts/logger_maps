<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper\Fixtures;

class Users {
  /** @var string Table name */
  public string $table = 'users';

  /** @var array Records */
  public array $records = [
    [
      'id' => 1,
      'login' => 'user1',
      'password' => '$2y$10$pkU.MoxFxTQa60qSvtchtuyLGveyTEST1bAdhWh.uznq2JXChDXPi',
      'admin' => 1
    ],
    [
      'id' => 2,
      'login' => 'user2',
      'password' => '$2y$10$pkU.MoxFxTQa60qSvtchtuyLGveyTEST2bAdhWh.uznq2JXChDXPi',
      'admin' => 0
    ]
  ];
}
