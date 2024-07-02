<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use uLogger\Component\Db;

abstract class AbstractMapper {

  protected Db $db;

  /**
   * @param Db $db
   */
  public function __construct(Db $db) { $this->db = $db; }

}
