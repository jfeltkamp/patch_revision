<?php

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "boolean",
 *   label = @Translation("FieldPatchPlugin for all field type boolean."),
 *   field_types = {
 *     "boolean",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchBoolean extends FieldPatchUndiffable {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'boolean';
  }

  /**
   * {@inheritdoc}
   */
  public function setWidgetFeedback(&$field, $feedback) {
    $result = $this->mergeFeedback($feedback);
    $properties = array_keys($this->getFieldProperties());

    foreach ($feedback as $key => $col) {
      foreach ($properties as $property) {
        if(isset($col[$property]['applied'])) {
          if ($col[$property]['applied'] === FALSE) {
            $field['#attributes']['class'][] = "pr-apply-{$property}-failed";
          }
        }
      }
    }

    $message = ($result['applied'])
      ? $this->getMergeSuccessMessage($result['code'])
      : $this->getMergeConflictMessage();

    if (isset($field['#type']) && $field['#type'] == 'container') {
      $field['patch_result'] = [
        '#markup' => $message,
        '#weight' => -50,
        '#prefix' => "<strong class=\"pr-success-message {$result['type']}\">",
        '#suffix' => "</strong>",
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDataIntegrity($value) {
    return in_array($value, [0,1]);
  }
}