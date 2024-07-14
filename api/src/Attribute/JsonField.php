<?php
declare(strict_types = 1);

namespace uLogger\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonField {
  private ?string $name;

  /**
   * @param string|null $name
   */
  public function __construct(?string $name = null) {
    $this->name = $name;
  }

  public function getName(): ?string {
    return $this->name;
  }
}
