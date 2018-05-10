<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "boolean",
 *   label = @Translation("FieldPatchPlugin for all field type boolean."),
 *   fieldTypes = {
 *     "boolean",
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
class FieldPatchBoolean extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'boolean';
  }

  /**
   * {@inheritdoc}
   */
  protected function setFeedbackClasses(&$field, $feedback) {
    $properties = array_keys($this->getFieldProperties());
    foreach ($feedback as $key => $col) {
      foreach ($properties as $property) {
        if (isset($col[$property]['applied'])) {
          if ($col[$property]['applied'] === FALSE) {
            $field['#attributes']['class'][] = "cr-apply-{$property}-failed";
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDataIntegrity($value) {
    return in_array($value, [0, 1]);
  }

}
