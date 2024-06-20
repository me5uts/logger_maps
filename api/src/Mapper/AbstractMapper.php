<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use uLogger\Component\Db;
use uLogger\Exception\ServerException;

abstract class AbstractMapper {

  protected Db $db;

  /**
   * @param Db $db
   */
  public function __construct(Db $db) { $this->db = $db; }

  /**
   * @throws ServerException
   */
  protected function mapRowToEntity(array $row) {
    try {
      $calledClass = get_called_class();
      $entityName = str_replace('Mapper', 'Entity', $calledClass);
      $entityClass = new ReflectionClass($entityName);
      $instance = $entityClass->newInstanceWithoutConstructor();
    } catch (ReflectionException $e) {
      throw new ServerException($e->getMessage());
    }
    foreach ($entityClass->getProperties() as $property) {
      $attributes = $property->getAttributes(Column::class);
      foreach ($attributes as $attribute) {
        $attributeInstance = $attribute->newInstance();
        $fieldName = $attributeInstance->getName() ?? $property->getName();
        if (isset($row[$fieldName])) {
          if (!$property->isPublic()) {
            $property->setAccessible(true);
          }
          $value = $row[$fieldName];
          $castedValue = $this->castValue($value, $property);
          $property->setValue($instance, $castedValue);
        } elseif ($property->hasDefaultValue()) {
          $property->setValue($instance, $property->getDefaultValue());
        } else {
          throw new ServerException("Missing value for field {$property->getName()}");
        }
      }
    }
    return $instance;
  }

  private function castValue(mixed $value, ReflectionProperty $property) {
    $type = $property->getType();

    if (!$type) {
      return $value;
    }

    $typeName = $type->getName();

    return match ($typeName) {
      'int' => (int) $value,
      'float' => (float) $value,
      'string' => (string) $value,
      'bool' => (bool) $value,
      'array' => (array) $value,
      default => $value,
    };
  }

}
