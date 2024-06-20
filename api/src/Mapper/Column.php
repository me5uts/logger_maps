<?php
declare(strict_types = 1);

namespace uLogger\Mapper;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {

  private ?string $name;

  /**
   * @param string|null $name
   */
  public function __construct(?string $name = null) { $this->name = $name; }

  public function getName(): ?string {
    return $this->name;
  }
}
