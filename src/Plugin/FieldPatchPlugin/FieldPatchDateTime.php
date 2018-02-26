<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "datetime",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   description = @Translation("Diff plugin for all texts with summary."),
 *   field_types = {
 *     "datetime",
 *     "timestamp",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchDateTime extends FieldPatchUndiffable {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'datetime';
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData($data) {

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
      }
    }

    return $data;
  }

}