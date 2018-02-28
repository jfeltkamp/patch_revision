<?php

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "list",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   field_types = {
 *     "list_string",
 *     "list_float",
 *     "list_integer",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchList extends FieldPatchUndiffable {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($property, $patch, $value_old) {
    $patch = json_decode($patch, true);
    if (empty($patch)) {
      return [
        '#markup' => $value_old
      ];
    } else {
      return [
        '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
          '@old' => $patch['old'],
          '@new' => $patch['new'],
        ])
      ];
    }
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

}