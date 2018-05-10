<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "datetime",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   fieldTypes = {
 *     "datetime",
 *     "timestamp",
 *   },
 *   properties = {
 *     "value" = {
 *       "label" = @Translation("Value"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchDateTime extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'datetime';
  }

  /**
   * Get a formatted date value.
   *
   * @param int|string $value
   *   A unix time stamp.
   *
   * @return string
   *   A formatted date string.
   */
  public function getFormattedValue($value) {
    // $value = "1519732604";.
    if (!preg_match('/^[0-9]*$/', $value)) {
      $timezone = new \DateTimeZone('UTC');
      $date_time = new DrupalDateTime($value, $timezone);
      $value = $date_time->format('U');
    }
    $object = $this->dateFormatter->format($value, 'medium');
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDataDb($data) {
    switch ($this->getFieldType()) {
      case 'timestamp':
        $format = 'U';
        break;

      default:
        $format = 'Y-m-d\TH:i:s';
    }

    foreach ($data as $key => $value) {
      foreach ($this->getFieldProperties() as $name => $default) {
        if ($value[$name] instanceof DrupalDateTime) {
          $data[$key][$name] = $value[$name]->format($format);
        }
        else {
          $data[$key][$name] = (string) $value[$name];
        }
      }
    }
    return $data;
  }

}
