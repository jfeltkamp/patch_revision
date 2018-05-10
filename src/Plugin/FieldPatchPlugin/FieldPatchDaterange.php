<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "daterange",
 *   label = @Translation("FieldPatchPlugin for field type daterange."),
 *   fieldTypes = {
 *     "daterange",
 *   },
 *   properties = {
 *     "value" = {
 *       "label" = @Translation("Start"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *     "end_value" = {
 *       "label" = @Translation("End"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchDaterange extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'daterange';
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDataDb($data) {
    foreach ($data as $key => $value) {
      foreach ($this->getFieldProperties() as $name => $default) {
        if ($value[$name] instanceof DrupalDateTime) {
          $data[$key][$name] = $value[$name]->format('Y-m-d\TH:i:s');
        }
        else {
          $data[$key][$name] = (string) $value[$name];
        }
      }
    }
    return $data;
  }

}
